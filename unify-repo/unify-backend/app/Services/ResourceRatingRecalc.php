<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\ResourceRating;

/**
 * Round-2 (audit V2-14): ONE recalculation path for family ratings. The
 * online endpoint recomputed averages while the offline-sync path only
 * upserted the row, so ratings that arrived via /offline/sync left
 * average_rating/rating_count stale until someone rated online again.
 *
 * The score lives on the family's current HEAD row (not superseded / not
 * deleted) — the row students actually see in listings.
 */
class ResourceRatingRecalc
{
    public static function run(string $familyId): ?array
    {
        $head = Resource::where('family_id', $familyId)
            ->where('is_superseded', false)
            ->where('is_deleted_content', false)
            ->orderByDesc('version')
            ->first();

        if (! $head) {
            return null;
        }

        // avg() is NULL when only self-ratings exist — ?? 0 keeps round() off
        // the PHP 8.x null-coercion path.
        $avg = ResourceRating::where('resource_family_id', $familyId)
            ->where('is_self_rating', false)
            ->avg('rating');
        $count = ResourceRating::where('resource_family_id', $familyId)->count();

        $head->update([
            'average_rating' => round($avg ?? 0, 1),
            'rating_count' => $count,
        ]);

        return ['average' => $head->average_rating, 'count' => $count];
    }
}
