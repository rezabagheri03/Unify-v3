<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function declareAcademicStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:normal,conditional,gpa_a,final_semester',
            'acknowledged' => 'required|boolean|accepted',
        ]);

        $user = $request->user();

        $oldStatus = $user->academic_status_declared;

        $user->update([
            'academic_status_declared' => $request->status,
            'academic_status_last_declared_at' => now(),
            'academic_status_declaration_count' => $user->academic_status_declaration_count + 1,
            'is_honor_system_acknowledged' => true,
        ]);

        // Log to history (C2 fix)
        AcademicStatusHistory::create([
            'id' => Str::uuid(),
            'student_id' => $user->id,
            'status' => $request->status,
            'semester_id' => \App\Models\Semester::where('is_current', true)->value('id'),
            'declared_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        // Abuse detection for final_semester
        if ($request->status === 'final_semester') {
            $count = AcademicStatusHistory::where('student_id', $user->id)
                ->where('status', 'final_semester')
                ->distinct('semester_id')
                ->count();

            if ($count > 2) {
                // Flag abuse
                \App\Models\HonorFlag::create([
                    'id' => Str::uuid(),
                    'student_id' => $user->id,
                    'flag_type' => 'final_semester_abuse',
                    'count' => $count,
                ]);
            }
        }

        return response()->json(['message' => 'وضعیت تحصیلی ثبت شد']);
    }

    public function getAcademicStatus(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'academic_status_declared' => $user->academic_status_declared,
            'academic_status_declaration_count' => $user->academic_status_declaration_count,
            'academic_status_last_declared_at' => $user->academic_status_last_declared_at,
            'is_honor_system_acknowledged' => $user->is_honor_system_acknowledged,
        ]);
    }
}