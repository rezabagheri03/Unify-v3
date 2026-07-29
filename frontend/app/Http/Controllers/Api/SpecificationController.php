<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseSpecification;
use App\Services\ShamsiService;
use Illuminate\Http\Request;

class SpecificationController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseSpecification::with(['course', 'professor', 'semester'])
            ->where('is_active', true)
            ->whereHas('semester', fn($q) => $q->where('is_current', true));

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('course', fn($q) => $q->where('name', 'like', "%$search%"))
                  ->orWhereHas('course', fn($q) => $q->where('code', 'like', "%$search%"));
        }

        if ($request->has('day')) {
            $query->where('day_of_week', $request->day);
        }

        $specs = $query->paginate(20);

        // Use ShamsiService for consistent date formatting
        $data = $specs->items()->map(function ($spec) {
            if ($spec->exam_date_final_g) {
                $spec->shamsi_final = ShamsiService::toShamsi($spec->exam_date_final_g);
            }
            return $spec;
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $specs->currentPage(),
                'total_pages' => $specs->lastPage(),
                'total_items' => $specs->total(),
            ]
        ]);
    }
}