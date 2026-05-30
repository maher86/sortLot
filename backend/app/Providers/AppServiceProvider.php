<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Package;
use App\Models\Supplier;
use App\Policies\CustomerPolicy;
use App\Policies\ItemPolicy;
use App\Policies\PackagePolicy;
use App\Policies\SupplierPolicy;
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
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
    }
}
