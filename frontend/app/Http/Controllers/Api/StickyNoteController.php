<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ResourceStickyNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class StickyNoteController extends Controller
{
    public function store(Request $request, $resourceId)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $resource = Resource::findOrFail($resourceId);

        // Sanitize input
        $sanitizedNote = strip_tags($request->note);
        $encrypted = Crypt::encryptString($sanitizedNote);

        ResourceStickyNote::updateOrCreate(
            [
                'student_id' => $user->id,
                'resource_family_id' => $resource->family_id,
            ],
            [
                'id' => Str::uuid(),
                'note' => $encrypted,
            ]
        );

        return response()->json(['message' => 'یادداشت خصوصی ذخیره شد']);
    }

    public function show(Request $request, $resourceId)
    {
        $user = $request->user();
        $resource = Resource::findOrFail($resourceId);

        $note = ResourceStickyNote::where('student_id', $user->id)
            ->where('resource_family_id', $resource->family_id)
            ->first();

        if (!$note) {
            return response()->json(['note' => null]);
        }

        try {
            $decrypted = Crypt::decryptString($note->note);
        } catch (\Exception $e) {
            $decrypted = 'خطا در رمزگشایی';
        }

        return response()->json(['note' => $decrypted]);
    }
}