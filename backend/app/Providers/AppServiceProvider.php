<?php

namespace App\Providers;

use App\Models\Item;
use App\Models\Package;
use App\Policies\ItemPolicy;
use App\Policies\PackagePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Item::class, ItemPolicy::class);
        Gate::policy(Package::class, PackagePolicy::class);
    }
}
