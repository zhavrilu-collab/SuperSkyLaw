<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OfficeKind;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Rules\ValidIban;
use App\Rules\ValidOib;
use App\Services\AdminConsoleWebhookService;
use App\Services\CoreAuthService;
use App\Services\CourtRegister;
use App\Services\LawyerDirectoryLookupService;
use App\Services\OrganizationOnboardingService;
use App\Support\OfficeName;
use App\Support\UserOrganizationNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizationRegistrationController extends Controller
{
    public function create(LawyerDirectoryLookupService $directory): View
    {
        try {
            $directoryCount = $directory->count();
        } catch (\Throwable) {
            $directoryCount = 0;
        }

        return view('auth.register-organization', [
            'isLoggedIn' => Auth::check(),
            'officeKinds' => OfficeKind::cases(),
            'directoryCount' => $directoryCount,
            'directoryLookupUrl' => route('register.organization.directory'),
            'courtLookupUrl' => route('register.organization.court'),
            'courtKinds' => [OfficeKind::Firm->value, OfficeKind::ForeignBranch->value],
            'soleKind' => OfficeKind::Sole->value,
        ]);
    }

    public function directory(Request $request, LawyerDirectoryLookupService $directory): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        return response()->json([
            'results' => $directory->search($data['q']),
            'meta' => [
                'count' => $directory->count(),
            ],
        ]);
    }

    public function court(Request $request, CourtRegister $court): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'office_kind' => ['required', Rule::enum(OfficeKind::class)],
        ]);

        $kind = OfficeKind::from($data['office_kind']);
        if (! $kind->usesCourtRegister()) {
            return response()->json(['matches' => []]);
        }

        try {
            $matches = $court->findByCompanyName($data['name']);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first(),
                'matches' => [],
            ], 422);
        }

        return response()->json(['matches' => $matches]);
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
        CourtRegister $court,
    ): RedirectResponse {
        $rules = [
            'office_kind' => ['required', Rule::enum(OfficeKind::class)],
            'name' => ['required', 'string', 'max:255'],
            'oib' => ['required', 'string', new ValidOib, Rule::unique('organizations', 'oib')],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:50'],
            'organization_email' => ['required', 'email', 'max:255'],
            'iban' => ['required', 'string', 'max:34', new ValidIban],
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
        $kind = OfficeKind::from($data['office_kind']);
        $mbs = null;

        if ($kind->usesCourtRegister()) {
            $official = $court->officialRecord($data['oib']);
            if (! OfficeName::matches($official['name'], $data['name'])) {
                throw ValidationException::withMessages([
                    'oib' => 'Naziv u sudskom registru („'.$official['name'].'”) ne odgovara upisanom nazivu.',
                ]);
            }
            $mbs = $official['mbs'];
        }

        $organization = DB::transaction(function () use ($data, $coreAuth, $kind, $mbs) {
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
                'office_kind' => $kind,
                'slug' => OrganizationOnboardingService::makeUniqueSlug($data['name']),
                'status' => OrganizationStatus::Pending,
                'plan' => 'basic',
                'status_changed_at' => now(),
                'email' => $data['organization_email'],
                'oib' => preg_replace('/\s+/', '', $data['oib']),
                'mbs' => $mbs,
                'phone' => $data['phone'],
                'city' => $data['city'],
                'address' => $data['address'],
                'iban' => strtoupper(preg_replace('/\s+/', '', $data['iban']) ?? ''),
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
            ->with('status', 'Ured je prijavljen i čeka odobrenje administratora.');
    }
}
