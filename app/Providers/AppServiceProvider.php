<?php

namespace App\Providers;

use App\Services\OrganizationRbacService;
use App\Services\PlanFeatureService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('admin-sync', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        Blade::if('perm', function (string $permission): bool {
            if (! app()->bound('currentOrganization') || auth()->id() === null) {
                return false;
            }

            return app(OrganizationRbacService::class)->can(
                app('currentOrganization')->id,
                (int) auth()->id(),
                $permission,
            );
        });

        Blade::if('planFeature', function (string $feature): bool {
            if (! app()->bound('currentOrganization')) {
                return false;
            }

            return app(PlanFeatureService::class)->allows(app('currentOrganization'), $feature);
        });
    }
}
