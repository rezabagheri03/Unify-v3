<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ResourceUploadCount;
use App\Services\ShamsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use finfo;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $query = Resource::where('status', 'approved')
            ->where('is_superseded', false)
            ->with(['course', 'professor']);

        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->professor_id) {
            $query->where('professor_id', $request->professor_id);
        }

        $resources = $query->orderBy('created_at_g', 'desc')->paginate(20);

        return response()->json(['data' => $resources->items()]);
    }

    public function upload(Request $request)
    {
        $user = $request->user();

        // Quota check (5/day)
        $today = now()->toDateString();
        $count = ResourceUploadCount::where('user_id', $user->id)->where('date', $today)->value('count') ?? 0;
        if ($count >= 5) {
            return response()->json(['message' => 'سهمیه آپلود روزانه تمام شده'], 429);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:51200',
            'title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'professor_id' => 'required|exists:users,id',
        ]);

        $file = $request->file('file');
        
        // Magic bytes check (finfo)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getRealPath());
        if (!in_array($mime, ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            return response()->json(['message' => 'فقط PDF و DOCX مجاز است'], 422);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "resources/{$request->course_id}/{$request->professor_id}/{$filename}";
        
        Storage::disk('public')->put($path, file_get_contents($file));

        $resource = Resource::create([
            'id' => Str::uuid(),
            'course_id' => $request->course_id,
            'professor_id' => $request->professor_id,
            'uploader_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $path,
            'file_size_bytes' => $file->getSize(),
            'file_mime' => $mime,
            'created_at_g' => now(),
            'shamsi_original' => ShamsiService::toShamsi(now()),
            'status' => $user->role === 'professor' ? 'approved' : 'pending',
            'badge_type' => $user->role === 'professor' ? 'professor' : null,
            'family_id' => null,
        ]);

        // Update daily count
        ResourceUploadCount::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['count' => \DB::raw('count + 1')]
        );

        // TODO: Notify approvers via Notification + Pushe

        return response()->json($resource, 201);
    }

    public function download($id)
    {
        $resource = Resource::findOrFail($id);
        
        // Increment download stats
        $resource->increment('download_count');
        $resource->update(['last_downloaded_at' => now()]);

        // TODO: Check daily download limit (20/day)

        return Storage::disk('public')->download($resource->file_path);
    }
}