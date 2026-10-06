<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CoreAuthService;
use App\Support\UserOrganizationNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoreOAuthCallbackController extends Controller
{
    public function __construct(
        private readonly CoreAuthService $coreAuth,
    ) {}

    public function create(Request $request): RedirectResponse
    {
        $token = $request->string('token')->toString();

        if ($token === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'OAuth prijava nije uspjela.',
            ]);
        }

        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->acceptJson()
            ->timeout(8)
            ->get(\App\Support\CoreApiUrl::endpoint('/auth/me'));

        if (! $response->successful()) {
            return redirect()->route('login')->withErrors([
                'email' => 'OAuth prijava nije uspjela.',
            ]);
        }

        $coreUser = $response->json('user');

        if (! is_array($coreUser) || ! isset($coreUser['id'], $coreUser['email'])) {
            return redirect()->route('login')->withErrors([
                'email' => 'Neočekivan odgovor platforme.',
            ]);
        }

        $user = $this->coreAuth->resolveLocalUser(
            (int) $coreUser['id'],
            (string) $coreUser['email'],
            (string) ($coreUser['name'] ?? ''),
        );

        $this->coreAuth->storeTokenInSession($token);
        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectAfterLogin();
    }

    private function redirectAfterLogin(): RedirectResponse
    {
        $path = UserOrganizationNavigation::postAuthRedirectPath(
            UserOrganizationNavigation::organizationUsers((int) Auth::id()),
        );

        return $path !== null
            ? redirect($path)
            : redirect()->route('register.organization');
    }
}
