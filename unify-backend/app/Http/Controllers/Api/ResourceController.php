<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ResourceUploadCount;
use App\Models\DownloadDailyCount;
use App\Models\ResourceDownloadLog;
use App\Models\Notification;
use App\Services\ShamsiService;
use App\Services\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use finfo;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $query = Resource::where('status', 'approved')
            ->where('is_superseded', false)
            ->where('is_deleted_content', false)
            ->with(['course', 'professor']);

        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->professor_id) {
            $query->where('professor_id', $request->professor_id);
        }
        if ($request->search) {
            $s = $request->search;
            $query->where('title', 'like', "%{$s}%");
        }

        $sort = $request->get('sort', 'newest');
        $query->when($sort === 'rated', fn ($q) => $q->orderByDesc('average_rating'))
              ->when($sort === 'downloaded', fn ($q) => $q->orderByDesc('download_count'))
              ->when($sort === 'newest', fn ($q) => $q->orderByDesc('created_at_g'));

        return response()->json(['data' => $query->paginate(20)->items()]);
    }

    public function show(Request $request, $id)
    {
        $resource = Resource::with(['course', 'professor', 'uploader'])->findOrFail($id);
        return response()->json($resource);
    }

    public function upload(Request $request)
    {
        $user = $request->user();

        // Quota 5/day (F05)
        $today = now()->toDateString();
        $count = ResourceUploadCount::where('user_id', $user->id)->where('date', $today)->value('count') ?? 0;
        if ($count >= 5) {
            return response()->json(['message' => 'سهمیه آپلود روزانه تمام شده', 'code' => 'QUOTA_EXCEEDED'], 429);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:51200',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'course_id' => 'required|exists:courses,id',
            'professor_id' => $user->role === 'professor' ? 'nullable' : 'required|exists:users,id',
        ]);

        // Professors upload on their own behalf; students must name the professor.
        $professorId = $user->role === 'professor' ? $user->id : $request->professor_id;

        $file = $request->file('file');

        // Magic bytes check (F05 security)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getRealPath());
        if (! in_array($mime, ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            return response()->json(['message' => 'فقط PDF و DOCX مجاز است', 'code' => 'INVALID_TYPE'], 422);
        }

        // Filename safety: uuid only, never original (H10)
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Professor uploads are auto-approved and stored directly at the permanent
        // path; student uploads are staged under temp/ until approval (F05).
        $isProfessor = $user->role === 'professor';
        if ($isProfessor) {
            $path = "resources/{$request->course_id}/{$professorId}/{$filename}";
            Storage::disk('public')->put($path, file_get_contents($file));
            $tempPath = null;
        } else {
            $path = null;
            $tempPath = 'temp/' . $user->id . '/' . $filename;
            Storage::disk('public')->put($tempPath, file_get_contents($file));
        }

        $resource = Resource::create([
            'id' => Str::uuid(),
            'course_id' => $request->course_id,
            'professor_id' => $professorId,
            'uploader_id' => $user->id,
            'title' => InputSanitizer::clean($request->title, 255),
            'description' => InputSanitizer::clean($request->description ?? '', 1000),
            'file_path' => $path,
            'temp_path' => $tempPath,
            'file_size_bytes' => $file->getSize(),
            'file_mime' => $mime,
            'created_at_g' => now(),
            'shamsi_original' => ShamsiService::toShamsi(now()),
            'status' => $isProfessor ? 'approved' : 'pending',
            'badge_type' => $isProfessor ? 'professor' : null,
            'family_id' => null, // Observer sets family_id = id (C1)
        ]);

        $uploadCount = ResourceUploadCount::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['count' => 0]
        );
        $uploadCount->increment('count');

        if (! $isProfessor) {
            $this->notifyApprovers($resource);
        }

        return response()->json($resource, 201);
    }

    /** Upload a new version of an existing resource family (F05/F06). */
    public function newVersion(Request $request, $id)
    {
        $user = $request->user();
        $parent = Resource::findOrFail($id);

        $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:51200',
            'title' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getRealPath());
        if (! in_array($mime, ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            return response()->json(['message' => 'فقط PDF و DOCX مجاز است', 'code' => 'INVALID_TYPE'], 422);
        }

        $isProfessor = $user->role === 'professor';
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "resources/{$parent->course_id}/{$parent->professor_id}/{$filename}";

        if ($isProfessor) {
            Storage::disk('public')->put($path, file_get_contents($file));
            $tempPath = null;
        } else {
            $tempPath = 'temp/' . $user->id . '/' . $filename;
            Storage::disk('public')->put($tempPath, file_get_contents($file));
        }

        $maxVersion = Resource::where('family_id', $parent->family_id)->max('version') ?? 1;

        $newVersion = Resource::create([
            'id' => Str::uuid(),
            'course_id' => $parent->course_id,
            'professor_id' => $parent->professor_id,
            'uploader_id' => $user->id,
            'title' => $request->title ?? $parent->title,
            'description' => $parent->description,
            'file_path' => $isProfessor ? $path : null,
            'temp_path' => $tempPath,
            'file_size_bytes' => $file->getSize(),
            'file_mime' => $mime,
            'created_at_g' => now(),
            'status' => $isProfessor ? 'approved' : 'pending',
            'badge_type' => $parent->badge_type,
            'family_id' => $parent->family_id,
            'previous_version_id' => $parent->id,
            'version' => $maxVersion + 1,
        ]);

        // Old version superseded; hard-delete content after 30 days (F05)
        $parent->update([
            'is_superseded' => true,
            'scheduled_hard_delete_at' => now()->addDays(30),
        ]);

        return response()->json($newVersion, 201);
    }

    public function download(Request $request, $id)
    {
        $user = $request->user();
        $resource = Resource::where('id', $id)
            ->where('status', 'approved')
            ->where('is_deleted_content', false)
            ->firstOrFail();

        // 20/day download limit (H5)
        $today = now()->toDateString();
        $daily = DownloadDailyCount::where('student_id', $user->id)->where('date', $today)->value('count') ?? 0;
        if ($daily >= 20) {
            return response()->json(['message' => 'سقف روزانه ۲۰ دانلود', 'code' => 'DOWNLOAD_LIMIT'], 429);
        }

        $path = $resource->file_path;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'فایل یافت نشد', 'code' => 'NOT_FOUND'], 404);
        }

        $resource->increment('download_count');
        $resource->update(['last_downloaded_at' => now()]);

        // LRU + daily stats (H2/H5)
        ResourceDownloadLog::create([
            'resource_id' => $resource->id,
            'student_id' => $user->id,
            'downloaded_at' => now(),
            'file_size_bytes' => $resource->file_size_bytes,
        ]);
        $downloadStat = DownloadDailyCount::firstOrCreate(
            ['student_id' => $user->id, 'date' => $today],
            ['count' => 0, 'total_bytes' => 0]
        );
        $downloadStat->increment('count');
        $downloadStat->increment('total_bytes', (int) $resource->file_size_bytes);

        return Storage::disk('public')->download($path);
    }

    private function notifyApprovers(Resource $resource): void
    {
        // Notify experts/admins of the department (polling + Pushe via Notification table)
        $approvers = \App\Models\User::whereIn('role', ['expert', 'admin'])
            ->where(function ($q) use ($resource) {
                $q->whereNull('department_id')->orWhere('department_id', $resource->course->department_id ?? 'CS');
            })
            ->pluck('id');

        foreach ($approvers as $approverId) {
            Notification::create([
                'id' => Str::uuid(),
                'user_id' => $approverId,
                'type' => 'resource_new',
                'title' => 'منبع جدید در انتظار تأیید',
                'body' => $resource->title,
                'priority' => 'high',
                'created_at' => now(),
            ]);
        }
    }
}
