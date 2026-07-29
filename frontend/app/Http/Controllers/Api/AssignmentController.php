<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignmentTracker;
use App\Services\ShamsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        return AssignmentTracker::where('student_id', $request->user()->id)
            ->orderBy('due_date_g')
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'specification_id' => 'required',
            'title' => 'required|string',
            'due_date_g' => 'required|date',
        ]);

        $assignment = AssignmentTracker::create([
            'id' => Str::uuid(),
            'student_id' => $request->user()->id,
            'specification_id' => $request->specification_id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date_g' => $request->due_date_g,
            'shamsi_original' => ShamsiService::toShamsi($request->due_date_g),
            'status' => 'pending',
        ]);

        return response()->json($assignment, 201);
    }

    // Late detection (called by cron)
    public static function markLate()
    {
        AssignmentTracker::where('status', 'pending')
            ->where('due_date_g', '<', now())
            ->update(['status' => 'late']);
    }
}