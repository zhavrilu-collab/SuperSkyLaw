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
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\MatterParty;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyListTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_and_sorts_parties(): void
    {
        [$user, $organization] = $this->office();

        $this->party($organization, 'Ana Anić', PartyKind::Person, 'Split');
        $company = $this->party($organization, 'Željko Čengić', PartyKind::Company, 'Zagreb', '12345678903');

        $this->actingAs($user)
            ->get(route('organization.parties.index', $organization->slug))
            ->assertOk()
            ->assertSee('predmeti-sort')
            ->assertSeeInOrder(['Ana Anić', 'Željko Čengić']);

        $this->actingAs($user)
            ->get(route('organization.parties.index', ['slug' => $organization->slug, 'sort' => 'city', 'dir' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Željko Čengić', 'Ana Anić']);

        $this->actingAs($user)
            ->get(route('organization.parties.index', ['slug' => $organization->slug, 'q' => '12345678903']))
            ->assertOk()
            ->assertSee('Željko Čengić')
            ->assertDontSee('Ana Anić');

        $this->actingAs($user)
            ->get(route('organization.parties.index', ['slug' => $organization->slug, 'kind' => 'company']))
            ->assertOk()
            ->assertSee('Željko Čengić')
            ->assertDontSee('Ana Anić');

        $matter = Matter::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Alimentacija',
            'internal_number' => '2026/001',
            'kind' => MatterKind::Civil,
            'status' => MatterStatus::Active,
            'office_position' => OfficePosition::Plaintiff,
            'billing_method' => BillingMethod::Hourly,
            'created_by_user_id' => $user->id,
        ]);
        MatterParty::query()->create([
            'organization_id' => $organization->id,
            'matter_id' => $matter->id,
            'party_id' => $company->id,
            'role' => MatterPartyRole::Client,
            'side' => PartySide::Client,
        ]);
        MatterDocument::query()->create([
            'organization_id' => $organization->id,
            'matter_id' => $matter->id,
            'original_name' => 'tuzba.pdf',
            'path' => 'predmeti/tuzba.pdf',
            'size_bytes' => 10,
        ]);
        Invoice::query()->create([
            'organization_id' => $organization->id,
            'matter_id' => $matter->id,
            'party_id' => $company->id,
            'number' => '2026-014',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'status' => 'unpaid',
            'subtotal_cents' => 10000,
            'vat_cents' => 2500,
            'total_cents' => 12500,
            'buyer_name' => $company->name,
        ]);

        $this->actingAs($user)
            ->get(route('organization.parties.edit', [$organization->slug, $company->id]))
            ->assertRedirect(route('organization.parties.show', [$organization->slug, $company->id, 'tab' => 'podaci']));

        $this->actingAs($user)
            ->get(route('organization.parties.show', [$organization->slug, $company->id]))
            ->assertOk()
            ->assertSee('Podaci')
            ->assertSee('Dokumenti')
            ->assertSee('Predmeti')
            ->assertSee('Računi')
            ->assertSee('value="Željko Čengić"', false);

        $this->actingAs($user)
            ->get(route('organization.parties.show', [$organization->slug, $company->id, 'tab' => 'predmeti']))
            ->assertOk()
            ->assertSee('Alimentacija')
            ->assertSee('Klijent');

        $this->actingAs($user)
            ->get(route('organization.parties.show', [$organization->slug, $company->id, 'tab' => 'dokumenti']))
            ->assertOk()
            ->assertSee('tuzba.pdf');

        $this->actingAs($user)
            ->get(route('organization.parties.show', [$organization->slug, $company->id, 'tab' => 'racuni']))
            ->assertOk()
            ->assertSee('2026-014');

        $lawyer = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $lawyer->id,
            'role' => OrganizationRole::Lawyer,
        ]);

        $this->actingAs($lawyer)
            ->get(route('organization.parties.show', [$organization->slug, $company->id, 'tab' => 'racuni']))
            ->assertOk()
            ->assertSee('pristupom financijama')
            ->assertDontSee('2026-014');
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function office(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ured Stranke',
            'slug' => 'ured-stranke',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'oib' => '69435151535',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$user, $organization];
    }

    private function party(Organization $organization, string $name, PartyKind $kind, string $city, ?string $oib = null): Party
    {
        return Party::query()->create([
            'organization_id' => $organization->id,
            'kind' => $kind,
            'name' => $name,
            'city' => $city,
            'oib' => $oib,
        ]);
    }
}
