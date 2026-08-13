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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use finfo;

class ResourceController extends Controller
{
    /**
     * SEC-01/P0 fix: the stored extension is derived ONLY from the finfo-validated
     * MIME type — never from the client-supplied filename. A polyglot file named
     * `evil.php` whose bytes are a PDF is therefore stored as `.pdf`, and anything
     * whose bytes are not PDF/DOCX is rejected before it touches the disk.
     */
    private const MIME_TO_EXT = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    /**
     * SEC-01/P0 + SEC-05 fix: uploads live on the `local` disk (storage/app,
     * OUTSIDE the public web root) and are served exclusively through the
     * authorized `download` endpoint. Legacy files stored under the public disk
     * keep working via a read fallback until they are rotated out.
     */
    private const DISK = 'local';

    public function index(Request $request)
    {
        $query = Resource::where('status', 'approved')
            ->where('is_superseded', false)
            ->where('is_deleted_content', false)
            // SEC-04 fix: trim eager-loaded users to public-safe columns
            // (mobile/email never leave the server).
            ->with(['course', 'professor:id,first_name,last_name']);

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
        $resource = Resource::with(['course', 'professor:id,first_name,last_name', 'uploader:id,first_name,last_name,role'])
            ->findOrFail($id);

        // SEC-05 fix: non-approved resources are only visible to staff, the
        // uploader, and the owning professor (everyone else gets a plain 404).
        if ($resource->status !== 'approved') {
            $user = $request->user();
            $privileged = in_array($user->role, ['expert', 'admin', 'owner', 'head_of_dept'])
                || $resource->uploader_id === $user->id
                || $resource->professor_id === $user->id;
            if (! $privileged) {
                abort(404);
            }
        }

        return response()->json($resource);
    }

    public function upload(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:51200',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'course_id' => 'required|exists:courses,id',
            'professor_id' => $user->role === 'professor' ? 'nullable' : 'required|exists:users,id',
        ]);

        // Quota 5/day (F05) — claimed atomically (post-audit F-16), AFTER
        // validation so a malformed request never burns a slot, but BEFORE any
        // byte hits the disk.
        if (! \App\Services\DailyQuota::claim(ResourceUploadCount::class, 'user_id', $user->id, 5)) {
            return response()->json(['message' => 'سهمیه آپلود روزانه تمام شده', 'code' => 'QUOTA_EXCEEDED'], 429);
        }

        // Professors upload on their own behalf; students must name the professor.
        $professorId = $user->role === 'professor' ? $user->id : $request->professor_id;

        $file = $request->file('file');

        // Magic bytes check (F05 security) — content decides the extension.
        $mime = $this->validatedMime($file);
        if ($mime === null) {
            return response()->json(['message' => 'فقط PDF و DOCX مجاز است', 'code' => 'INVALID_TYPE'], 422);
        }
        $filename = Str::uuid() . '.' . self::MIME_TO_EXT[$mime];

        // Professor uploads are auto-approved and stored directly at the permanent
        // path; student uploads are staged under temp/ until approval (F05).
        // PERF-07 fix: putFileAs streams the upload instead of loading the whole
        // (up to 50MB) file into PHP memory.
        $isProfessor = $user->role === 'professor';
        if ($isProfessor) {
            $path = "resources/{$request->course_id}/{$professorId}/{$filename}";
            Storage::disk(self::DISK)->putFileAs(dirname($path), $file, basename($path));
            $tempPath = null;
        } else {
            $path = null;
            $tempPath = 'temp/' . $user->id . '/' . $filename;
            Storage::disk(self::DISK)->putFileAs(dirname($tempPath), $file, basename($tempPath));
        }

        try {
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
        } catch (\Throwable $e) {
            // Post-audit F-16: don't leak an orphaned file on a failed row create.
            Storage::disk(self::DISK)->delete($path ?? $tempPath);
            throw $e;
        }

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

        // SEC-02 fix: only staff, the owning professor, or the original uploader
        // may push a new version into a family.
        $isStaff = in_array($user->role, ['expert', 'admin', 'owner']);
        $allowed = $isStaff
            || $parent->professor_id === $user->id
            || $parent->uploader_id === $user->id;
        if (! $allowed) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:51200',
            'title' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $mime = $this->validatedMime($file);
        if ($mime === null) {
            return response()->json(['message' => 'فقط PDF و DOCX مجاز است', 'code' => 'INVALID_TYPE'], 422);
        }

        // Round-2 (V2-05): versions ARE uploads — they stage up to 50MB on
        // disk and must burn the same daily quota, or the 5/day intake bound
        // is defeated exactly on the path that needs it least.
        if (! \App\Services\DailyQuota::claim(ResourceUploadCount::class, 'user_id', $user->id, 5)) {
            return response()->json(['message' => 'سهمیه آپلود روزانه تمام شده', 'code' => 'QUOTA_EXCEEDED'], 429);
        }

        $isProfessor = $user->role === 'professor';
        $filename = Str::uuid() . '.' . self::MIME_TO_EXT[$mime];
        $path = "resources/{$parent->course_id}/{$parent->professor_id}/{$filename}";

        if ($isProfessor) {
            Storage::disk(self::DISK)->putFileAs(dirname($path), $file, basename($path));
            $tempPath = null;
        } else {
            $tempPath = 'temp/' . $user->id . '/' . $filename;
            Storage::disk(self::DISK)->putFileAs(dirname($tempPath), $file, basename($tempPath));
        }

        $maxVersion = Resource::where('family_id', $parent->family_id)->max('version') ?? 1;

        try {
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
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($isProfessor ? $path : $tempPath);
            throw $e;
        }

        // SEC-02 fix (supersede-before-approve): the parent is only superseded
        // when the new version is auto-approved (professor path). A student
        // pending version leaves the approved parent untouched until an
        // approver accepts it (see ResourceApprovalController::approve).
        if ($isProfessor) {
            $parent->update([
                'is_superseded' => true,
                'scheduled_hard_delete_at' => now()->addDays(30),
            ]);
        }

        return response()->json($newVersion, 201);
    }

    public function download(Request $request, $id)
    {
        $user = $request->user();
        $resource = Resource::where('id', $id)
            ->where('status', 'approved')
            ->where('is_deleted_content', false)
            ->firstOrFail();

        // 20/day download limit (H5) — atomic claim (post-audit F-16).
        $today = now()->toDateString();
        if (! \App\Services\DailyQuota::claim(DownloadDailyCount::class, 'student_id', $user->id, 20)) {
            return response()->json(['message' => 'سقف روزانه ۲۰ دانلود', 'code' => 'DOWNLOAD_LIMIT'], 429);
        }

        // Resolve on the secured local disk first; fall back to legacy public-
        // disk files so pre-migration uploads keep downloading.
        $path = $resource->file_path;
        $disk = null;
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            $disk = self::DISK;
        } elseif ($path && Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }
        if (! $disk) {
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
        // count was already claimed atomically above; only bytes remain.
        DownloadDailyCount::where('student_id', $user->id)->where('date', $today)
            ->increment('total_bytes', (int) $resource->file_size_bytes);

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $downloadName = Str::slug($resource->title) . '.' . ($ext ?: 'pdf');

        return Storage::disk($disk)->download($path, $downloadName);
    }

    /** finfo-validated MIME or null when the bytes are not PDF/DOCX. */
    private function validatedMime($file): ?string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getRealPath());
        return array_key_exists($mime, self::MIME_TO_EXT) ? $mime : null;
    }

    private function notifyApprovers(Resource $resource): void
    {
        // Notify experts/admins of the department (polling + Pushe via Notification table)
        // Post-audit F-16: a course without a department used to silently page
        // the CS experts ('CS' string fallback) — now it pages admins only.
        $deptId = $resource->course->department_id ?? null;
        if ($deptId === null) {
            \Illuminate\Support\Facades\Log::warning('resource without department — notifying admins only', ['resource_id' => $resource->id]);
        }
        $approvers = \App\Models\User::whereIn('role', ['expert', 'admin'])
            ->where(function ($q) use ($deptId) {
                $q->whereNull('department_id');
                if ($deptId !== null) {
                    $q->orWhere('department_id', $deptId);
                } else {
                    $q->orWhere('role', 'admin');
                }
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
            Cache::forget("notifications:unread:{$approverId}");
        }
    }
}
