<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Resource;
use App\Observers\ResourceObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Resource::observe(ResourceObserver::class);
    }
}