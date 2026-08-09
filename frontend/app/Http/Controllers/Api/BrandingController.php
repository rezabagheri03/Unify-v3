<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|max:2048',
        ]);

        $path = $request->file('logo')->store('branding', 'public');

        // Save to system config
        \App\Models\SystemConfig::updateOrCreate(
            ['key' => 'logo_path'],
            ['value' => $path]
        );

        return response()->json(['path' => $path]);
    }
}