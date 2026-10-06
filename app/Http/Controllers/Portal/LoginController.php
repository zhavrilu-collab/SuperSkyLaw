<?php

namespace App\Http\Controllers\Portal;

use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(string $slug): View|RedirectResponse
    {
        $organization = $this->organization($slug);
        if (auth('client')->check() && auth('client')->user()->organization_id === $organization->id) {
            return redirect()->route('portal.home', $slug);
        }

        return view('portal.login', ['organization' => $organization]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $organization = $this->organization($slug);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('client')->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
            'organization_id' => $organization->id,
        ], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Prijava nije uspjela.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('portal.home', $slug);
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        Auth::guard('client')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login', $slug);
    }

    private function organization(string $slug): Organization
    {
        $organization = Organization::query()->where('slug', $slug)->firstOrFail();
        if ($organization->status !== OrganizationStatus::Active) {
            abort(403, 'Ured nije aktivan.');
        }
        if (! app(PlanFeatureService::class)->allows($organization, 'client_portal')) {
            abort(403, 'Portal klijenta nije u trenutnom planu.');
        }

        return $organization;
    }
}
