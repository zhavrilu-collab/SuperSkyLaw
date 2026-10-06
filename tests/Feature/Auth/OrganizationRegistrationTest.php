<?php

namespace Tests\Feature\Auth;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrganizationRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'identity.core_auth_enabled' => false,
            'admin_console.webhook_url' => null,
        ]);
    }

    public function test_guest_can_register_pending_organization(): void
    {
        $response = $this->post(route('register.organization'), [
            'name' => 'Nova tvrtka d.o.o.',
            'oib' => '12345678903',
            'organization_email' => 'office@nova-tvrtka.hr',
            'phone' => '0912345678',
            'city' => 'Zagreb',
            'admin_name' => 'Ana Vlasnik',
            'admin_email' => 'ana@nova-tvrtka.hr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('registration.pending'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Nova tvrtka d.o.o.',
            'status' => OrganizationStatus::Pending->value,
            'email' => 'office@nova-tvrtka.hr',
            'oib' => '12345678903',
        ]);

        $user = User::query()->where('email', 'ana@nova-tvrtka.hr')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        $organization = Organization::query()->where('name', 'Nova tvrtka d.o.o.')->first();
        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner->value,
        ]);
    }

    public function test_registration_rejects_invalid_oib(): void
    {
        $this->post(route('register.organization'), [
            'name' => 'Krivi OIB d.o.o.',
            'oib' => '12345678901',
            'organization_email' => 'office@krivi-oib.hr',
            'admin_name' => 'Test User',
            'admin_email' => 'test@krivi-oib.hr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('oib');
    }

    public function test_registration_dispatches_webhook_when_configured(): void
    {
        Queue::fake();

        config([
            'admin_console.webhook_url' => 'http://127.0.0.1:8001/api/webhooks/tenants/registered',
            'admin_console.webhook_secret' => 'test-secret',
            'admin_console.application_slug' => 'legal-saas',
        ]);

        $this->post(route('register.organization'), [
            'name' => 'Webhook tvrtka d.o.o.',
            'oib' => '12345678903',
            'organization_email' => 'office@webhook.hr',
            'admin_name' => 'Webhook User',
            'admin_email' => 'webhook@webhook.hr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('registration.pending'));

        Queue::assertPushed(\App\Jobs\NotifyAdminConsoleJob::class);
    }
}
