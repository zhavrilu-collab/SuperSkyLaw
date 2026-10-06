<?php

namespace App\Services;

use App\Exceptions\PlatformTwoFactorRequiredException;
use App\Models\User;
use App\Support\CoreApiUrl;
use App\Support\CoreAuthSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CoreAuthService
{
    public function isEnabled(): bool
    {
        return config('identity.core_auth_enabled') === true;
    }

    /**
     * @return array{token: string, core_user_id: int, email: string, name: string}
     */
    public function registerWithCredentials(string $name, string $email, string $password, ?string $passwordConfirmation = null): array
    {
        $response = Http::acceptJson()
            ->timeout(8)
            ->post(CoreApiUrl::endpoint('/auth/register'), [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation ?? $password,
            ]);

        if ($response->status() === 422) {
            $message = $response->json('errors.email.0')
                ?? $response->json('message')
                ?? 'Registracija računa nije uspjela.';

            throw ValidationException::withMessages([
                'email' => [(string) $message],
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'email' => ['Platforma za registraciju trenutačno nije dostupna. Pokušajte kasnije.'],
            ]);
        }

        $token = $response->json('token');
        $user = $response->json('user');

        if (! is_string($token) || $token === '' || ! is_array($user) || ! isset($user['id'])) {
            throw ValidationException::withMessages([
                'email' => ['Neočekivan odgovor platforme za registraciju.'],
            ]);
        }

        return [
            'token' => $token,
            'core_user_id' => (int) $user['id'],
            'email' => (string) ($user['email'] ?? $email),
            'name' => (string) ($user['name'] ?? $name),
        ];
    }

    /**
     * @return array{token: string, core_user_id: int, email: string, name: string}
     */
    public function loginWithCredentials(string $email, string $password): array
    {
        $response = Http::acceptJson()
            ->timeout(8)
            ->post(CoreApiUrl::endpoint('/auth/login'), [
                'email' => $email,
                'password' => $password,
                'application_slug' => config('identity.application_slug'),
            ]);

        if ($response->status() === 422) {
            throw ValidationException::withMessages([
                'email' => ['Neispravna e-mail adresa ili lozinka.'],
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'email' => ['Platforma za prijavu trenutačno nije dostupna. Pokušajte kasnije.'],
            ]);
        }

        if ($response->json('two_factor_required') === true) {
            $challengeToken = (string) $response->json('two_factor_token');

            throw new PlatformTwoFactorRequiredException(
                $challengeToken,
                $this->twoFactorChallengeUrl($challengeToken),
            );
        }

        $token = $response->json('token');
        $user = $response->json('user');

        if (! is_string($token) || $token === '' || ! is_array($user) || ! isset($user['id'])) {
            throw ValidationException::withMessages([
                'email' => ['Neočekivan odgovor platforme za prijavu.'],
            ]);
        }

        return [
            'token' => $token,
            'core_user_id' => (int) $user['id'],
            'email' => (string) ($user['email'] ?? $email),
            'name' => (string) ($user['name'] ?? ''),
        ];
    }

    public function twoFactorChallengeUrl(string $challengeToken): string
    {
        $base = rtrim((string) config('identity.core_api_url'), '/');

        return $base.'/platform/prijava/2fa?'.http_build_query([
            'two_factor_token' => $challengeToken,
            'application_slug' => config('identity.application_slug'),
            'return_url' => route('auth.core.callback', [], true),
        ]);
    }

    public function resolveLocalUser(int $coreUserId, string $email, string $name = ''): User
    {
        $user = User::query()->where('core_user_id', $coreUserId)->first();

        if ($user !== null) {
            return $user;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            if ($user->core_user_id === null) {
                $user->forceFill(['core_user_id' => $coreUserId])->save();
            }

            return $user->fresh();
        }

        return User::query()->create([
            'core_user_id' => $coreUserId,
            'name' => $name !== '' ? $name : $email,
            'email' => $email,
            'password' => bcrypt(str()->random(32)),
            'email_verified_at' => now(),
        ]);
    }

    public function logoutToken(?string $token): void
    {
        if (! is_string($token) || $token === '') {
            return;
        }

        Http::withToken($token)
            ->acceptJson()
            ->timeout(5)
            ->post(CoreApiUrl::endpoint('/auth/logout'));
    }

    public function storeTokenInSession(string $token): void
    {
        session([CoreAuthSession::TOKEN => $token]);
    }

    public function sessionToken(): ?string
    {
        $token = session(CoreAuthSession::TOKEN);

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function forgetSessionToken(): void
    {
        session()->forget(CoreAuthSession::TOKEN);
    }

    public function googleRedirectUrl(): string
    {
        return $this->oauthRedirectUrl('google');
    }

    public function microsoftRedirectUrl(): string
    {
        return $this->oauthRedirectUrl('microsoft');
    }

    public function isGoogleLoginAvailable(): bool
    {
        return $this->isEnabled() && config('identity.google_oauth_enabled') === true;
    }

    public function isMicrosoftLoginAvailable(): bool
    {
        return $this->isEnabled() && config('identity.microsoft_oauth_enabled') === true;
    }

    public function isUnifiedLoginEnabled(): bool
    {
        return $this->isEnabled() && config('identity.unified_login_enabled') === true;
    }

    public function unifiedLoginUrl(): string
    {
        $base = rtrim((string) config('identity.core_api_url'), '/');

        return $base.'/platform/prijava?'.http_build_query([
            'application_slug' => config('identity.application_slug'),
        ]);
    }

    private function oauthRedirectUrl(string $provider): string
    {
        $returnUrl = route('auth.core.callback', [], true);
        $base = rtrim((string) config('identity.core_api_url'), '/');

        return $base.'/auth/'.$provider.'/redirect?'.http_build_query([
            'application_slug' => config('identity.application_slug'),
            'return_url' => $returnUrl,
        ]);
    }
}
