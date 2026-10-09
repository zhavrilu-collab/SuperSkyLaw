<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\TimeEntry;
use App\Models\TimelineEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_matter_opens_on_the_first_stage_and_tabs_keep_their_records(): void
    {
        [$user, $org] = $this->office();

        $this->actingAs($user)
            ->post(route('organization.matters.store', $org->slug), $this->payload())
            ->assertRedirect();

        $matter = Matter::query()->first();
        $this->assertSame('Priprema', $matter->stages()->first()->name);
        $this->assertNull($matter->stages()->first()->ended_on);

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id]))
            ->assertOk()
            ->assertSee('Podaci predmeta')
            ->assertSee('Stadij rada')
            ->assertSee('Priprema')
            ->assertSee('Ured');

        $this->actingAs($user)
            ->post(route('organization.matters.stages.store', [$org->slug, $matter->id]), [
                'name' => 'Pregovori',
                'body' => 'Usuglašena je cijena.',
            ])
            ->assertRedirect(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'podaci']));

        $stages = $matter->stages()->reorder()->orderBy('position')->get();
        $this->assertNotNull($stages->first()->ended_on);
        $this->assertSame('Pregovori', $stages->last()->name);

        $this->actingAs($user)
            ->put(route('organization.matters.update', [$org->slug, $matter->id]), [
                'title' => 'Novi predmet',
                'kind' => 'civil',
                'office_position' => 'plaintiff',
                'billing_method' => 'hourly',
                'filed_on' => '2026-03-12',
            ])
            ->assertRedirect();

        $this->assertSame('2026-03-12', $matter->fresh()->filed_on->toDateString());

        $this->actingAs($user)
            ->post(route('organization.matters.notes.store', [$org->slug, $matter->id]), [
                'body' => 'Interna bilješka za ured.',
            ])
            ->assertRedirect(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'biljeske']));

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'biljeske']))
            ->assertOk()
            ->assertSee('Interna bilješka za ured.')
            ->assertSee('Vide ih samo ljudi u uredu');

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'rokovi']))
            ->assertOk()
            ->assertSee('Rokovi i ročišta')
            ->assertSee('Zastara');

        MatterDocument::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'folder' => 'Podnesci',
            'kind' => 'brief',
            'original_name' => 'prijedlog.pdf',
            'path' => 'predmeti/prijedlog.pdf',
            'size_bytes' => 12,
            'uploaded_by_user_id' => $user->id,
        ]);
        MatterDocument::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'folder' => 'Punomoci',
            'kind' => 'power',
            'original_name' => 'punomoc.pdf',
            'path' => 'predmeti/punomoc.pdf',
            'size_bytes' => 12,
            'uploaded_by_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'dokumenti', 'vrsta' => 'brief']))
            ->assertOk()
            ->assertSee('prijedlog.pdf')
            ->assertDontSee('punomoc.pdf');

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'kronologija']))
            ->assertOk()
            ->assertSee('Kronologija')
            ->assertSee('Dodaj zapis');
    }

    public function test_matter_lists_every_activity_and_its_own_ledger(): void
    {
        [$user, $org] = $this->office();

        $this->actingAs($user)
            ->post(route('organization.matters.store', $org->slug), $this->payload())
            ->assertRedirect();

        $matter = Matter::query()->first();

        TimelineEntry::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'type' => 'action',
            'body' => 'Poslana ponuda klijentu',
            'occurred_at' => '2026-04-01 09:00:00',
            'user_id' => $user->id,
            'visible_to_client' => false,
        ]);
        TimeEntry::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'user_id' => $user->id,
            'description' => 'Sastanak s klijentom',
            'started_at' => '2026-04-02 10:00:00',
            'ended_at' => '2026-04-02 11:00:00',
            'minutes' => 60,
            'hourly_rate_cents' => 10000,
            'status' => 'approved',
        ]);
        Invoice::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'number' => 'R-1-2026',
            'issue_date' => '2026-04-03',
            'due_date' => '2026-05-03',
            'status' => 'unpaid',
            'subtotal_cents' => 140000,
            'vat_cents' => 35000,
            'total_cents' => 175000,
            'paid_cents' => 0,
            'vat_rate' => 25,
            'buyer_name' => 'Klijent',
        ]);

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'aktivnosti']))
            ->assertOk()
            ->assertSee('Poslana ponuda klijentu')
            ->assertSee('Sastanak s klijentom')
            ->assertSee('R-1-2026')
            ->assertSee('1.750,00 EUR')
            ->assertSee('Priprema');

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'obracun']))
            ->assertOk()
            ->assertSee('Obračun predmeta')
            ->assertSee('100,00 EUR')
            ->assertSee('1.750,00 EUR')
            ->assertSee('Nije na računu')
            ->assertSee('Neplaćeno')
            ->assertSee('Unesi sate');

        $this->actingAs($user)
            ->post(route('organization.expenses.store', $org->slug), [
                'matter_id' => $matter->id,
                'category' => 'travel',
                'description' => 'Put na pregovore',
                'amount' => 84,
                'bill_to' => 'client',
                'return_to' => 'matter',
            ])
            ->assertRedirect(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'obracun']));

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'obracun']))
            ->assertOk()
            ->assertSee('Put na pregovore')
            ->assertSee('84,00 EUR');

        $lawyer = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $org->id,
            'user_id' => $lawyer->id,
            'role' => OrganizationRole::Lawyer,
        ]);
        $matter->assignees()->attach($lawyer->id);

        $this->actingAs($lawyer)
            ->get(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'aktivnosti']))
            ->assertOk()
            ->assertSee('Poslana ponuda klijentu')
            ->assertSee('Sastanak s klijentom')
            ->assertSee('R-1-2026')
            ->assertDontSee('1.750,00 EUR')
            ->assertSee('Obračun');

        $this->actingAs($lawyer)
            ->post(route('organization.time.store', $org->slug), [
                'matter_id' => $matter->id,
                'description' => 'Pregled spisa',
                'minutes' => 30,
                'return_to' => 'matter',
            ])
            ->assertRedirect(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'obracun']));

        $this->actingAs($lawyer)
            ->get(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'obracun']))
            ->assertOk()
            ->assertSee('Pregled spisa')
            ->assertSee('Unesi sate')
            ->assertDontSee('1.750,00 EUR')
            ->assertDontSee('84,00 EUR')
            ->assertDontSee('Iznos EUR');
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function office(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ured',
            'slug' => 'ured-kartica',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'oib' => '12345678903',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$user, $organization];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'title' => 'Novi predmet',
            'kind' => 'civil',
            'office_position' => 'plaintiff',
            'billing_method' => 'hourly',
            'client_name' => 'Klijent',
            'client_kind' => 'person',
            'client_oib' => '12345678903',
        ];
    }
}
