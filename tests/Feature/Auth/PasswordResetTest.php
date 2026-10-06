<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_shows_forgot_password_link(): void
    {
        config([
            'identity.core_auth_enabled' => false,
            'identity.unified_login_enabled' => false,
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Zaboravili ste lozinku?')
            ->assertSee(route('password.request'), false);
    }

    public function test_login_points_forgot_password_at_core_when_core_auth_is_on(): void
    {
        config([
            'identity.core_auth_enabled' => true,
            'identity.unified_login_enabled' => false,
            'identity.core_api_url' => 'http://127.0.0.1:8001',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Zaboravili ste lozinku?')
            ->assertSee('http://127.0.0.1:8001/zaboravljena-lozinka', false);
    }

    public function test_reset_link_can_be_requested_and_password_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Pošalji poveznicu');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'Poslali smo vam poveznicu za novu lozinku.');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
                ->assertOk()
                ->assertSee('Postavi novu lozinku');

            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'nova-lozinka',
                'password_confirmation' => 'nova-lozinka',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('nova-lozinka', $user->fresh()->password));
    }
}
