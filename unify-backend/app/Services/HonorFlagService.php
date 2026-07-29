<?php

namespace App\Services;

use App\Models\AcademicStatusHistory;

class HonorFlagService
{
    public static function checkFinalSemesterAbuse(string $studentId): bool
    {
        $count = AcademicStatusHistory::where('student_id', $studentId)
            ->where('status', 'final_semester')
            ->distinct('semester_id')
            ->count();

        return $count > 2;
    }
}