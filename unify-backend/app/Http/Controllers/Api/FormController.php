<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormController extends Controller
{
    /** List forms (dept scoped for non-admin, university-wide for all). */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Form::where('is_active', true)
            ->with('department');

        if (! in_array($user->role, ['admin', 'owner'])) {
            $query->where(function ($q) use ($user) {
                $q->where('is_university_level', true)
                  ->orWhere('department_id', $user->department_id);
            });
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:pdf,docx|max:20480', // 20MB
            'signature_guide' => 'nullable|string|max:200',
            'department_id' => 'nullable|exists:departments,id',
            'is_university_level' => 'nullable|boolean',
        ]);

        // Post-audit F-03: bind scope to role. Experts publish ONLY for their
        // own department and can never mark a form university-level (admin
        // capability per docs/ROLES); previously both were caller-controlled.
        if ($user->role === 'expert') {
            if ($request->boolean('is_university_level')) {
                return response()->json([
                    'message' => 'فرم دانشگاهی فقط توسط مدیر منتشر می‌شود',
                    'code' => 'UNIVERSITY_FORMS_ADMIN_ONLY',
                ], 403);
            }
            if ($request->filled('department_id') && $request->department_id !== $user->department_id) {
                return response()->json([
                    'message' => 'فقط برای دانشکده خودتان می‌توانید فرم منتشر کنید',
                    'code' => 'DEPT_SCOPE_VIOLATION',
                ], 403);
            }
            $request->merge(['department_id' => $user->department_id]);
        }

        $file = $request->file('file');
        $path = $file->store('forms/' . ($request->department_id ?? 'univ'), 'public');

        $form = Form::create([
            'id' => Str::uuid(),
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'department_id' => $request->department_id,
            'is_university_level' => $request->boolean('is_university_level'),
            'signature_guide' => $request->signature_guide ?? '—',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // F10, implemented for real (post-audit F-08, product decision): fan the
        // publication out to the affected students — everyone for a university
        // form, the owning department for a departmental one. Chunked inserts,
        // type 'general' (notifications.type is a MySQL ENUM — no new value is
        // added without a migration), same pattern as SemesterController.
        $targets = \App\Models\User::where('role', 'student')
            ->where('is_banned', false)
            ->when(! $form->is_university_level, fn ($q) => $q->where('department_id', $form->department_id))
            ->pluck('id');
        $now = now();
        foreach ($targets->chunk(200) as $chunk) {
            $rows = [];
            foreach ($chunk as $sid) {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $sid,
                    'type' => 'general',
                    'title' => $form->is_university_level ? 'فرم جدید دانشگاهی' : 'فرم جدید دانشکده',
                    'body' => $form->title,
                    'priority' => 'low',
                    'read' => false,
                    'created_at' => $now,
                ];
            }
            Notification::insert($rows);
            foreach ($chunk as $sid) {
                Cache::forget("notifications:unread:{$sid}");
            }
        }

        \App\Models\AuditLog::record($user->id, 'form_published', 'form', $form->id, $request, [
            'university_level' => $form->is_university_level,
            'notified' => $targets->count(),
        ]);

        return response()->json($form, 201);
    }

    public function download(Request $request, $id)
    {
        $form = Form::where('id', $id)->where('is_active', true)->firstOrFail();
        if (! Storage::disk('public')->exists($form->file_path)) {
            return response()->json(['message' => 'فایل یافت نشد', 'code' => 'NOT_FOUND'], 404);
        }
        return Storage::disk('public')->download($form->file_path, $form->title . '.pdf');
    }
}
