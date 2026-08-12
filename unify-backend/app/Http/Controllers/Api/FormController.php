<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Notification;
use Illuminate\Http\Request;
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

        // Notify students for university-level forms (F10)
        if ($form->is_university_level) {
            Notification::create([
                'id' => Str::uuid(),
                'user_id' => '990000001', // owner fallback
                'type' => 'resource_new',
                'title' => 'فرم جدید دانشگاهی',
                'body' => $form->title,
                'priority' => 'low',
                'created_at' => now(),
            ]);
        }

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
