<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\Enrollment;
use App\Models\CourseSpecification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SemesterController extends Controller
{
    public function createNewSemester(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'start_shamsi' => 'required',
            'end_shamsi' => 'required',
        ]);

        DB::transaction(function () use ($request) {
            // Soft hide old semester
            Semester::where('is_current', true)->update(['is_current' => false]);

            // Archive enrollments
            Enrollment::whereHas('semester', fn($q) => $q->where('is_current', true))
                ->where('status', 'finalized')
                ->update(['status' => 'archived']);

            // Deactivate old specs
            CourseSpecification::whereHas('semester', fn($q) => $q->where('is_current', true))
                ->update(['is_active' => false]);

            // Create new semester
            Semester::create([
                'id' => $request->name,
                'name' => $request->name,
                'is_current' => true,
                'global_state' => 'enrolling',
                'start_date_g' => now(),
                'end_date_g' => now()->addMonths(6),
            ]);
        });

        return response()->json(['message' => 'نیمسال جدید ایجاد شد']);
    }
}