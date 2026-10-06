<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\PlatformTwoFactorRequiredException;
use App\Http\Controllers\Controller;
use App\Services\CoreAuthService;
use App\Support\UserOrganizationNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly CoreAuthService $coreAuth,
    ) {}

    public function create(): View|RedirectResponse
    {
        if ($this->coreAuth->isUnifiedLoginEnabled()) {
            return redirect()->away($this->coreAuth->unifiedLoginUrl());
        }

        return view('auth.login', [
            'coreAuthEnabled' => $this->coreAuth->isEnabled(),
            'googleLoginUrl' => $this->coreAuth->isGoogleLoginAvailable() ? $this->coreAuth->googleRedirectUrl() : null,
            'microsoftLoginUrl' => $this->coreAuth->isMicrosoftLoginAvailable() ? $this->coreAuth->microsoftRedirectUrl() : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($this->coreAuth->isEnabled()) {
            return $this->loginViaCore($request, $credentials['email'], $credentials['password']);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Neispravna e-mail adresa ili lozinka.',
            ])->onlyInput('email');
        }

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

    public function destroy(Request $request): RedirectResponse
    {
        if ($this->coreAuth->isEnabled()) {
            $this->coreAuth->logoutToken($this->coreAuth->sessionToken());
            $this->coreAuth->forgetSessionToken();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function loginViaCore(Request $request, string $email, string $password): RedirectResponse
    {
        try {
            $coreSession = $this->coreAuth->loginWithCredentials($email, $password);
        } catch (PlatformTwoFactorRequiredException $exception) {
            return redirect()->away($exception->challengeUrl);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->onlyInput('email');
        }

        $user = $this->coreAuth->resolveLocalUser(
            $coreSession['core_user_id'],
            $coreSession['email'],
            $coreSession['name'],
        );

        $this->coreAuth->storeTokenInSession($coreSession['token']);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectAfterLogin();
    }
}
