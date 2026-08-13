<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Single-server mode: the built React PWA lives directly in public/
| (copied from frontend/dist). Laravel's built-in server serves real files
| statically; everything else (non-/api) falls back to index.html so the
| SPA's client-side routes (/login, /dashboard, ...) work on refresh.
*/

Route::get('/', fn () => response()->file(public_path('index.html')));

Route::get('/{path?}', function (?string $path = null) {
    if ($path !== null) {
        $file = public_path($path);
        if (is_file($file)) {
            return response()->file($file);
        }
    }
    return response()->file(public_path('index.html'));
})->where('path', '^(?!api/).*');
