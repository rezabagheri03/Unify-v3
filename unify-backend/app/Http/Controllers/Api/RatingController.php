<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ResourceRating;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RatingController extends Controller
{
    public function store(Request $request, $resourceId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $user = $request->user();
        $resource = Resource::findOrFail($resourceId);

        // Delete old rating
        ResourceRating::where('student_id', $user->id)
            ->where('resource_family_id', $resource->family_id)
            ->delete();

        $rating = ResourceRating::create([
            'id' => Str::uuid(),
            'student_id' => $user->id,
            'resource_family_id' => $resource->family_id,
            'rating' => $request->rating,
            'rated_at' => now(),
            'is_self_rating' => $resource->professor_id === $user->id,
        ]);

        // Round-2 (V2-14): shared recalc — same code path as offline sync.
        // The score lands on the family's current head row.
        $stats = \App\Services\ResourceRatingRecalc::run($resource->family_id);

        return response()->json(['message' => 'امتیاز ثبت شد', 'average' => $stats['average'] ?? $resource->average_rating]);
    }
}