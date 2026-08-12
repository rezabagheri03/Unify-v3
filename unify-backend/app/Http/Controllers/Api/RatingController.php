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

        // Recalculate average (exclude self ratings for average).
        // avg() returns NULL when only self-ratings exist — ?? 0 keeps
        // round() off the PHP 8.x null-coercion path.
        $avg = ResourceRating::where('resource_family_id', $resource->family_id)
            ->where('is_self_rating', false)
            ->avg('rating');

        $resource->update([
            'average_rating' => round($avg ?? 0, 1),
            'rating_count' => ResourceRating::where('resource_family_id', $resource->family_id)->count(),
        ]);

        return response()->json(['message' => 'امتیاز ثبت شد', 'average' => $resource->average_rating]);
    }
}