<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    /** Public branding config (login page / PWA theme). */
    public function publicConfig(Request $request)
    {
        return response()->json([
            'brand_name' => SystemConfig::get('brand_name', 'Unify'),
            'logo_path' => SystemConfig::get('logo_path', '/uploads/branding/logo.png'),
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,svg|max:2048',
        ]);

        $path = $request->file('logo')->store('branding', 'public');
        SystemConfig::set('logo_path', '/uploads/branding/' . basename($path));

        return response()->json(['path' => '/uploads/branding/' . basename($path)]);
    }

    public function setBrandName(Request $request)
    {
        $request->validate(['brand_name' => 'required|string|max:50']);
        SystemConfig::set('brand_name', $request->brand_name);
        return response()->json(['brand_name' => $request->brand_name]);
    }
}
