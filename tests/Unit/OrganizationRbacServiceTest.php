<?php

namespace Tests\Unit;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\OrganizationRbacService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationRbacServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationRbacService $rbac;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rbac = app(OrganizationRbacService::class);
    }

    public function test_partner_has_finance_and_team_permissions(): void
    {
        [$organizationId, $userId] = $this->seedMembership(OrganizationRole::Owner);

        $this->assertTrue($this->rbac->can($organizationId, $userId, 'team.manage'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'finance.access') || $this->rbac->can($organizationId, $userId, 'finance.view'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'matters.delete'));
    }

    public function test_lawyer_cannot_manage_team_or_finance(): void
    {
        [$organizationId, $userId] = $this->seedMembership(OrganizationRole::Lawyer);

        $this->assertFalse($this->rbac->can($organizationId, $userId, 'team.manage'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'matters.manage'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'finance.view'));
    }

    public function test_secretary_can_invoice_but_not_delete_matters(): void
    {
        [$organizationId, $userId] = $this->seedMembership(OrganizationRole::Secretary);

        $this->assertFalse($this->rbac->can($organizationId, $userId, 'team.manage'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'finance.view'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'matters.delete'));
    }

    public function test_trainee_cannot_see_finance(): void
    {
        [$organizationId, $userId] = $this->seedMembership(OrganizationRole::Trainee);

        $this->assertTrue($this->rbac->can($organizationId, $userId, 'time.manage'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'finance.view'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'matters.delete'));
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function seedMembership(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Test ured',
            'slug' => 'test-ured-'.$role->value,
            'status' => 'active',
            'plan' => 'basic',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$organization->id, $user->id];
    }
}
