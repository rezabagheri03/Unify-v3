<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'provider' => 'required|in:pushe,web_push',
            'platform' => 'required|in:web,android',
        ]);

        DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $request->token,
            ],
            [
                'id' => Str::uuid(),
                'provider' => $request->provider,
                'platform' => $request->platform,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['message' => 'Device token registered']);
    }

    /**
     * Post-audit F-15: logout/decommission path — the device stops receiving
     * pushes for this account (the old design kept tokens alive forever).
     */
    public function destroy(Request $request)
    {
        $request->validate(['token' => 'required|string|max:512']);

        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $request->token)
            ->update(['is_active' => false]);

        return response()->json(['message' => 'Device token deactivated']);
    }
}