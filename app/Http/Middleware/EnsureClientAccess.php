<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Services\PlanFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');
        $client = auth('client')->user();
        $organization = is_string($slug) ? Organization::query()->where('slug', $slug)->first() : null;

        if ($organization === null || $client === null || $client->organization_id !== $organization->id) {
            return redirect()->route('portal.login', ['slug' => $slug]);
        }

        if ($organization->status !== OrganizationStatus::Active) {
            abort(403, 'Ured nije aktivan.');
        }

        if (! app(PlanFeatureService::class)->allows($organization, 'client_portal')) {
            abort(403, 'Portal klijenta nije u trenutnom planu.');
        }

        app()->instance('currentOrganization', $organization);
        view()->share('org', $organization);
        view()->share('client', $client);

        return $next($request);
    }
}
