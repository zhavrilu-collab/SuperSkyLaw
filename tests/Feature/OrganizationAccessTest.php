<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\StaffInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_can_access_dashboard(): void
    {
        [$user, $organization] = $this->seedActiveOrganization(OrganizationRole::Owner);

        $this->actingAs($user)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('Aktivna d.o.o.');
    }

    public function test_pending_organization_redirects_to_waiting_page(): void
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Pending d.o.o.',
            'slug' => 'pending-firma',
            'status' => OrganizationStatus::Pending,
            'plan' => 'basic',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->actingAs($user)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertRedirect(route('registration.pending'));
    }

    public function test_suspended_organization_redirects_to_suspended_page(): void
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Suspendirana d.o.o.',
            'slug' => 'suspendirana-firma',
            'status' => OrganizationStatus::Suspended,
            'plan' => 'basic',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->actingAs($user)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertRedirect(route('organization.suspended', ['slug' => $organization->slug]));
    }

    public function test_active_office_is_opened_when_another_registration_is_still_pending(): void
    {
        $user = User::factory()->create();

        $active = Organization::query()->create([
            'name' => 'Odvjetnički ured Horvat',
            'slug' => 'ured-horvat',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        $pending = Organization::query()->create([
            'name' => 'Odvjetničko društvo Nova',
            'slug' => 'od-nova',
            'status' => OrganizationStatus::Pending,
            'plan' => 'basic',
        ]);

        foreach ([$active, $pending] as $organization) {
            OrganizationUser::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => OrganizationRole::Owner,
            ]);
        }

        $this->actingAs($user)
            ->get(route('registration.pending'))
            ->assertRedirect(route('organization.dashboard', 'ured-horvat'));
    }

    public function test_non_member_cannot_access_organization(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Tuđa d.o.o.',
            'slug' => 'tuda-firma',
            'status' => OrganizationStatus::Active,
            'plan' => 'basic',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('organization.dashboard', $organization->slug))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedActiveOrganization(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Aktivna d.o.o.',
            'slug' => 'aktivna-firma',
            'status' => OrganizationStatus::Active,
            'plan' => 'basic',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $organization];
    }
}
