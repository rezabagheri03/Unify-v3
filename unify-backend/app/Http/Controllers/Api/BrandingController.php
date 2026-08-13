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
            // Round-2 (V2-12): the public disk URL space is /storage/... via
            // `php artisan storage:link`. The historical '/uploads/...' path
            // (docs/01) mapped to nothing the pipeline creates — logo URLs
            // were guaranteed 404s. No stored value means "no logo set".
            'logo_path' => SystemConfig::get('logo_path'),
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,svg|max:2048',
        ]);

        $path = $request->file('logo')->store('branding', 'public');
        SystemConfig::set('logo_path', '/storage/' . $path);

        return response()->json(['path' => '/storage/' . $path]);
    }

    public function setBrandName(Request $request)
    {
        $request->validate(['brand_name' => 'required|string|max:50']);
        SystemConfig::set('brand_name', $request->brand_name);
        return response()->json(['brand_name' => $request->brand_name]);
    }
}
