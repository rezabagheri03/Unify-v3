<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignmentTracker;
use App\Models\Notification;
use App\Services\ShamsiService;
use App\Services\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = AssignmentTracker::where('student_id', $user->id)
            ->with('specification.course');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('specification_id')) {
            $query->where('specification_id', $request->specification_id);
        }

        return response()->json($query->orderBy('due_date_g')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'specification_id' => 'required|exists:course_specifications,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date_shamsi' => 'required|string',
            'reminder_before_hours' => 'nullable|integer|in:1,3,24,72',
        ]);

        $dueDateG = ShamsiService::toGregorian($request->due_date_shamsi);

        $assignment = AssignmentTracker::create([
            'id' => Str::uuid(),
            'student_id' => $request->user()->id,
            'specification_id' => $request->specification_id,
            'title' => InputSanitizer::clean($request->title, 255),
            'description' => InputSanitizer::clean($request->description ?? '', 1000),
            'due_date_g' => $dueDateG,
            'shamsi_original' => $request->due_date_shamsi,
            'reminder_before_hours' => $request->get('reminder_before_hours', 24),
            'status' => 'pending',
        ]);

        $assignment->shamsi_due = $request->due_date_shamsi;
        return response()->json($assignment, 201);
    }

    public function update(Request $request, $id)
    {
        $assignment = $this->ownAssignment($request, $id);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date_shamsi' => 'nullable|string',
            'reminder_before_hours' => 'nullable|integer|in:1,3,24,72',
        ]);

        $assignment->update([
            'title' => $request->title ? InputSanitizer::clean($request->title, 255) : $assignment->title,
            'description' => $request->has('description') ? InputSanitizer::clean($request->description, 1000) : $assignment->description,
            'due_date_g' => $request->due_date_shamsi ? ShamsiService::toGregorian($request->due_date_shamsi) : $assignment->due_date_g,
            'shamsi_original' => $request->due_date_shamsi ?? $assignment->shamsi_original,
            'reminder_before_hours' => $request->get('reminder_before_hours', $assignment->reminder_before_hours),
        ]);

        return response()->json($assignment);
    }

    public function submit(Request $request, $id)
    {
        $assignment = $this->ownAssignment($request, $id);

        $request->validate([
            'attachment' => 'nullable|file|mimes:pdf,docx,zip|max:20480',
        ]);

        $data = ['status' => 'submitted', 'submitted_at' => now()];
        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('assignments/' . $request->user()->id, 'public');
        }
        $assignment->update($data);

        return response()->json($assignment);
    }

    public function grade(Request $request, $id)
    {
        $user = $request->user();
        if (! in_array($user->role, ['professor', 'expert', 'admin'])) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }

        $request->validate(['grade' => 'required|numeric|min:0|max:20']);

        $assignment = AssignmentTracker::findOrFail($id);
        $assignment->update([
            'status' => 'graded',
            'grade' => $request->grade,
            'graded_by' => $user->id,
            'graded_at' => now(),
        ]);

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $assignment->student_id,
            'type' => 'assignment_graded',
            'title' => 'نمره تکلیف ثبت شد',
            'body' => $assignment->title . ' - ' . $request->grade,
            'priority' => 'high',
            'created_at' => now(),
        ]);

        return response()->json($assignment);
    }

    /** Mark late assignments (hourly cron, F12). */
    public static function markLate(): int
    {
        return AssignmentTracker::where('status', 'pending')
            ->where('due_date_g', '<', now())
            ->update(['status' => 'late']);
    }

    private function ownAssignment(Request $request, $id): AssignmentTracker
    {
        $assignment = AssignmentTracker::where('id', $id)
            ->where('student_id', $request->user()->id)
            ->firstOrFail();
        return $assignment;
    }
}
