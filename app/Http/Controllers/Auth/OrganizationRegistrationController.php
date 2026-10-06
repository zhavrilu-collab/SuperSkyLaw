<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Rules\ValidOib;
use App\Services\AdminConsoleWebhookService;
use App\Services\CoreAuthService;
use App\Services\OrganizationOnboardingService;
use App\Support\UserOrganizationNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register-organization', [
            'isLoggedIn' => Auth::check(),
        ]);
    }

    public function pending(): View|RedirectResponse
    {
        $orgUsers = UserOrganizationNavigation::organizationUsers((int) Auth::id());
        $pending = $orgUsers
            ->filter(fn (OrganizationUser $ou) => $ou->organization->status === OrganizationStatus::Pending)
            ->map(fn (OrganizationUser $ou) => $ou->organization)
            ->values();

        $path = UserOrganizationNavigation::landingPath($orgUsers);

        if ($path !== null) {
            return redirect($path);
        }

        if ($pending->isEmpty()) {
            return redirect()->route('register.organization');
        }

        return view('auth.registration-pending', [
            'pendingOrganizations' => $pending,
        ]);
    }

    public function status(): JsonResponse
    {
        $orgUsers = UserOrganizationNavigation::organizationUsers((int) Auth::id());

        return response()->json([
            'pending' => UserOrganizationNavigation::hasPending($orgUsers),
            'redirect_url' => UserOrganizationNavigation::landingPath($orgUsers),
            'organizations' => $orgUsers->map(fn (OrganizationUser $ou) => [
                'name' => $ou->organization->name,
                'slug' => $ou->organization->slug,
                'status' => $ou->organization->status->value,
            ])->values(),
        ]);
    }

    public function store(
        Request $request,
        AdminConsoleWebhookService $webhook,
        CoreAuthService $coreAuth,
    ): RedirectResponse {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'oib' => ['required', 'string', new ValidOib, Rule::unique('organizations', 'oib')],
            'organization_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
        ];

        if (! Auth::check()) {
            $rules['admin_email'] = ['required', 'email', 'max:255'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            $rules['admin_name'] = ['required', 'string', 'max:255'];

            if (! $coreAuth->isEnabled()) {
                $rules['admin_email'][] = Rule::unique('users', 'email');
            }
        }

        $data = $request->validate($rules);

        $organization = DB::transaction(function () use ($data, $coreAuth, $request) {
            $user = Auth::user();

            if ($user === null) {
                if ($coreAuth->isEnabled()) {
                    $coreSession = $coreAuth->registerWithCredentials(
                        $data['admin_name'],
                        $data['admin_email'],
                        $data['password'],
                        $data['password_confirmation'] ?? null,
                    );
                    $user = $coreAuth->resolveLocalUser(
                        $coreSession['core_user_id'],
                        $coreSession['email'],
                        $coreSession['name'],
                    );
                    $coreAuth->storeTokenInSession($coreSession['token']);
                } else {
                    $user = User::query()->create([
                        'name' => $data['admin_name'] ?? $data['admin_email'],
                        'email' => $data['admin_email'],
                        'password' => Hash::make($data['password']),
                        'email_verified_at' => now(),
                    ]);
                }
            }

            $organization = Organization::query()->create([
                'name' => $data['name'],
                'slug' => OrganizationOnboardingService::makeUniqueSlug($data['name']),
                'status' => OrganizationStatus::Pending,
                'plan' => 'basic',
                'status_changed_at' => now(),
                'email' => $data['organization_email'],
                'oib' => preg_replace('/\s+/', '', $data['oib']),
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
            ]);

            OrganizationUser::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => OrganizationRole::Owner,
            ]);

            return $organization;
        });

        $webhook->notifyTenantRegistered($organization);

        if (! Auth::check()) {
            Auth::login(User::query()->whereKey(
                OrganizationUser::query()->where('organization_id', $organization->id)->value('user_id'),
            )->firstOrFail());
            $request->session()->regenerate();
        }

        return redirect()
            ->route('registration.pending')
            ->with('status', 'Tvrtka je registrirana i čeka odobrenje administratora.');
    }
}
