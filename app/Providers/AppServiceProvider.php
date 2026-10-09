<?php

namespace App\Providers;

use App\Models\Organization;
use App\Services\OfficeCatalog;
use App\Services\OrganizationRbacService;
use App\Services\PlanFeatureService;
use App\Support\OfficeThemes;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Organization::created(function (Organization $organization): void {
            app(OfficeCatalog::class)->provision($organization);
        });

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

        View::composer(['layouts.app', 'layouts.guest', 'layouts.portal'], function (ViewInstance $view): void {
            $office = $this->officeForTheme($view);
            $key = OfficeThemes::resolve($office?->theme_color);
            $view->with('officeThemeKey', $key);
            $style = $office ? \App\Support\ThemeRecipes::effectiveStyle($office->theme_style) : null;
            $view->with('officeTheme', OfficeThemes::palette($key, $style));
        });
    }

    private function officeForTheme(ViewInstance $view): ?Organization
    {
        $data = $view->getData();

        foreach (['organization', 'org'] as $key) {
            if (($data[$key] ?? null) instanceof Organization) {
                return $data[$key];
            }
        }

        $invite = $data['invite'] ?? null;
        if (is_object($invite) && ($invite->organization ?? null) instanceof Organization) {
            return $invite->organization;
        }

        if (app()->bound('currentOrganization') && app('currentOrganization') instanceof Organization) {
            return app('currentOrganization');
        }

        return null;
    }
}
