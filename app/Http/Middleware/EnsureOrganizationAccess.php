<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');
        $user = $request->user();

        if (! is_string($slug) || $user === null) {
            abort(403);
        }

        $organization = Organization::query()->where('slug', $slug)->first();

        if ($organization === null) {
            abort(404);
        }

        $membership = OrganizationUser::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            abort(403, 'Nemate pristup ovoj tvrtki.');
        }

        if ($organization->status === OrganizationStatus::Suspended) {
            return redirect()->route('organization.suspended', ['slug' => $slug]);
        }

        if ($organization->status === OrganizationStatus::Pending) {
            return redirect()->route('registration.pending');
        }

        $request->attributes->set('currentOrganization', $organization);
        $request->attributes->set('currentOrganizationUser', $membership);
        app()->instance('currentOrganization', $organization);
        app()->instance('currentOrganizationUser', $membership);
        view()->share('org', $organization);
        view()->share('orgUser', $membership);

        return $next($request);
    }
}
