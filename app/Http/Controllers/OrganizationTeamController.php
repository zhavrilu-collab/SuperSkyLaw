<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\StaffInvite;
use App\Services\OrganizationRbacService;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationTeamController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly PlanFeatureService $plans,
    ) {}

    public function index(string $slug): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'team.manage');

        $members = OrganizationUser::query()
            ->with('user')
            ->where('organization_id', $organization->id)
            ->orderBy('role')
            ->get();

        $pendingInvites = StaffInvite::query()
            ->where('organization_id', $organization->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        return view('organization.team', [
            'organization' => $organization,
            'members' => $members,
            'pendingInvites' => $pendingInvites,
            'roles' => OrganizationRole::cases(),
        ]);
    }

    public function storeInvite(Request $request, string $slug): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'team.manage');

        $limit = $this->plans->limit($organization, 'user_limit');
        $memberCount = OrganizationUser::query()->where('organization_id', $organization->id)->count();
        if ($limit !== null && $memberCount >= $limit) {
            return back()->withErrors(['email' => 'Dosegnut je limit korisnika za trenutni plan.']);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(OrganizationRole::class)],
        ]);

        if ($data['role'] === OrganizationRole::Owner->value) {
            return back()->withErrors(['role' => 'Vlasnika se dodaje samo pri registraciji tvrtke.']);
        }

        if (OrganizationUser::query()
            ->where('organization_id', $organization->id)
            ->whereHas('user', fn ($q) => $q->where('email', strtolower($data['email'])))
            ->exists()) {
            return back()->withErrors(['email' => 'Korisnik već ima pristup tvrtki.']);
        }

        $invite = StaffInvite::issue(
            $organization,
            $data['email'],
            OrganizationRole::from($data['role']),
            (int) Auth::id(),
        );

        return back()->with('status', 'Pozivnica je kreirana. Link: '.route('staff-invite.show', $invite->token));
    }

    public function updateRole(Request $request, string $slug, OrganizationUser $member): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'team.manage');

        if ($member->organization_id !== $organization->id) {
            abort(404);
        }

        $data = $request->validate([
            'role' => ['required', Rule::enum(OrganizationRole::class)],
        ]);

        $newRole = OrganizationRole::from($data['role']);

        if ($member->isOwner() && $newRole !== OrganizationRole::Owner) {
            $ownerCount = OrganizationUser::query()
                ->where('organization_id', $organization->id)
                ->where('role', OrganizationRole::Owner)
                ->count();

            if ($ownerCount <= 1) {
                return back()->withErrors(['role' => 'Tvrtka mora imati barem jednog vlasnika.']);
            }
        }

        if ($newRole === OrganizationRole::Owner && ! $member->isOwner()) {
            return back()->withErrors(['role' => 'Vlasništvo se prenosi zasebnim postupkom.']);
        }

        $member->forceFill(['role' => $newRole])->save();

        return back()->with('status', 'Uloga je ažurirana.');
    }

    public function destroyMember(string $slug, OrganizationUser $member): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'team.manage');

        if ($member->organization_id !== $organization->id) {
            abort(404);
        }

        if ($member->isOwner()) {
            return back()->withErrors(['member' => 'Vlasnika nije moguće ukloniti.']);
        }

        $member->delete();

        return back()->with('status', 'Član je uklonjen iz tima.');
    }
}
