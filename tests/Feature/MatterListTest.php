<?php

namespace Tests\Feature;

use App\Enums\BillingMethod;
use App\Enums\MatterKind;
use App\Enums\MatterPartyRole;
use App\Enums\MatterStatus;
use App\Enums\OfficePosition;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PartyKind;
use App\Enums\PartySide;
use App\Models\Matter;
use App\Models\MatterParty;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterListTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_and_sorts_matters(): void
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ured Popis',
            'slug' => 'ured-popis',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'oib' => '12345678903',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->matter($organization, $user, '2026/002', 'Žalba', MatterKind::Labor, 'Čengić');
        $this->matter($organization, $user, '2026/001', 'Alimentacija', MatterKind::Civil, 'Anić');

        $this->actingAs($user)
            ->get(route('organization.matters.index', $organization->slug))
            ->assertOk()
            ->assertSee('Stranka')
            ->assertSee('Predmet spora')
            ->assertSeeInOrder(['2026/002', '2026/001']);

        $this->actingAs($user)
            ->get(route('organization.matters.index', ['slug' => $organization->slug, 'sort' => 'title', 'dir' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['Alimentacija', 'Žalba']);

        $this->actingAs($user)
            ->get(route('organization.matters.index', ['slug' => $organization->slug, 'q' => 'Čengić']))
            ->assertOk()
            ->assertSee('Žalba')
            ->assertDontSee('Alimentacija');

        $this->actingAs($user)
            ->get(route('organization.matters.index', ['slug' => $organization->slug, 'kind' => 'labor']))
            ->assertOk()
            ->assertSee('Žalba')
            ->assertDontSee('Alimentacija');
    }

    private function matter(Organization $organization, User $user, string $number, string $title, MatterKind $kind, string $client): Matter
    {
        $matter = Matter::query()->create([
            'organization_id' => $organization->id,
            'title' => $title,
            'internal_number' => $number,
            'kind' => $kind,
            'status' => MatterStatus::Active,
            'office_position' => OfficePosition::Plaintiff,
            'billing_method' => BillingMethod::Hourly,
            'created_by_user_id' => $user->id,
        ]);
        $party = Party::query()->create([
            'organization_id' => $organization->id,
            'kind' => PartyKind::Person,
            'name' => $client,
        ]);
        MatterParty::query()->create([
            'organization_id' => $organization->id,
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'role' => MatterPartyRole::Client,
            'side' => PartySide::Client,
        ]);

        return $matter;
    }
}
