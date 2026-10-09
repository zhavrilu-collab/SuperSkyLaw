<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Matter;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Services\OrganizationRbacService;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ResolvesOffice
{
    protected function office(): Organization
    {
        return app('currentOrganization');
    }

    protected function membership(): OrganizationUser
    {
        return app('currentOrganizationUser');
    }

    protected function authorizePerm(string $permission): void
    {
        app(OrganizationRbacService::class)->authorize(
            $this->office()->id,
            (int) auth()->id(),
            $permission,
        );
    }

    protected function authorizeFeature(string $feature): void
    {
        if (! app(PlanFeatureService::class)->allows($this->office(), $feature)) {
            abort(403, 'Ova značajka nije u trenutnom planu.');
        }
    }

    protected function findVisibleMatter(int $matterId): Matter
    {
        return Matter::query()->visibleTo($this->membership())->findOrFail($matterId);
    }

    protected function redirectToMatterLedger(Request $request, int $matterId, string $message): RedirectResponse
    {
        if ($request->input('return_to') === 'matter') {
            return redirect()
                ->route('organization.matters.show', [$this->office()->slug, $matterId, 'tab' => 'obracun'])
                ->with('status', $message);
        }

        return back()->with('status', $message);
    }
}
