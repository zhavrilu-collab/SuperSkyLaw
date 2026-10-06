<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrganizationSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['admin_sync.api_key' => 'test-sync-key']);
    }

    public function test_unauthenticated_sync_request_is_rejected(): void
    {
        $this->getJson('/api/admin/organizations')->assertUnauthorized();
    }

    public function test_sync_index_returns_organizations(): void
    {
        Organization::query()->create([
            'name' => 'Test d.o.o.',
            'slug' => 'test-firma',
            'status' => OrganizationStatus::Active,
            'plan' => 'basic',
        ]);

        $this->withToken('test-sync-key')
            ->getJson('/api/admin/organizations')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'test-firma');
    }

    public function test_sync_can_update_organization_status_and_plan(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Test d.o.o.',
            'slug' => 'test-firma',
            'status' => OrganizationStatus::Pending,
            'plan' => 'basic',
        ]);

        $this->withToken('test-sync-key')
            ->patchJson('/api/admin/organizations/'.$organization->id, [
                'status' => 'active',
                'plan' => 'premium',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.plan', 'premium');
    }
}
