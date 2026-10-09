<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Court;
use App\Models\CourtEvent;
use App\Models\DisputeCategory;
use App\Models\Matter;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\TariffCharge;
use App\Models\User;
use App\Services\CroatianCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterCatalogAndDeadlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_must_match_kind_and_case_number_is_split(): void
    {
        [$user, $org] = $this->office();
        $court = Court::query()->create([
            'name' => 'Općinski građanski sud u Zagrebu',
            'type' => 'municipal_civil',
            'city' => 'Zagreb',
            'active' => true,
            'sort' => 1,
        ]);
        $labor = DisputeCategory::query()->create([
            'kind' => 'labor',
            'name' => 'Nedopuštenost otkaza ugovora o radu',
            'hint' => 'Rokovi',
            'sort' => 1,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('organization.matters.store', $org->slug), $this->payload([
                'dispute_category_id' => $labor->id,
            ]))
            ->assertSessionHasErrors('dispute_category_id');

        $this->actingAs($user)
            ->post(route('organization.matters.store', $org->slug), $this->payload([
                'court_case_number' => '123/2026',
            ]))
            ->assertSessionHasErrors('court_case_number');

        $this->actingAs($user)
            ->post(route('organization.matters.store', $org->slug), $this->payload([
                'court_id' => $court->id,
                'court_case_number' => 'P-123/2026',
                'office_position' => 'defendant',
            ]))
            ->assertRedirect();

        $matter = Matter::query()->first();
        $this->assertSame($court->id, $matter->court_id);
        $this->assertSame('Općinski građanski sud u Zagrebu', $matter->court_name);
        $this->assertSame('P', $matter->case_mark);
        $this->assertSame('123', $matter->case_number);
        $this->assertSame(2026, $matter->case_year);
        $this->assertSame('defendant', $matter->office_position->value);
    }

    public function test_other_court_keeps_the_typed_name(): void
    {
        [$user, $org] = $this->office();

        $this->actingAs($user)
            ->post(route('organization.matters.store', $org->slug), $this->payload([
                'court_id' => 'other',
                'court_name' => 'Stalna služba u Samoboru',
            ]))
            ->assertRedirect();

        $matter = Matter::query()->first();
        $this->assertNull($matter->court_id);
        $this->assertSame('Stalna služba u Samoboru', $matter->court_name);
    }

    public function test_statutory_deadline_rolls_to_a_working_day_and_sets_phase(): void
    {
        [$user, $org] = $this->office();
        $category = DisputeCategory::query()->create([
            'kind' => 'labor',
            'name' => 'Nedopuštenost otkaza ugovora o radu',
            'hint' => 'Rokovi',
            'sort' => 1,
            'active' => true,
        ]);
        $matter = Matter::query()->create([
            'organization_id' => $org->id,
            'title' => 'Otkaz',
            'internal_number' => '2026/001',
            'kind' => 'labor',
            'status' => 'active',
            'office_position' => 'plaintiff',
            'dispute_category_id' => $category->id,
            'billing_method' => 'hourly',
        ]);

        $this->assertSame('new', $matter->fresh()->phase()->value);

        $this->actingAs($user)
            ->post(route('organization.matters.deadline.store', [$org->slug, $matter->id]), [
                'rule' => 'labor_zzp',
                'receipt_on' => '2026-10-09',
                'intent' => 'save',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $event = CourtEvent::query()->first();
        $this->assertSame('statutory', $event->origin);
        $this->assertTrue($event->is_preclusive);
        $this->assertSame('2026-10-23', $event->starts_at->timezone(CroatianCalendar::TIMEZONE)->toDateString());
        $this->assertSame('in_time', $matter->fresh()->load('courtEvents')->phase()->value);

        CourtEvent::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'type' => 'hearing',
            'title' => 'Ročište',
            'starts_at' => now()->addDays(20),
        ]);
        $event->forceFill(['starts_at' => now()->addDays(2)->setTime(23, 59)])->save();

        $this->assertSame('urgent', $matter->fresh()->load('courtEvents')->phase()->value);
    }

    public function test_completed_hearing_offers_a_tariff_line_once(): void
    {
        [$user, $org] = $this->office();
        $matter = Matter::query()->create([
            'organization_id' => $org->id,
            'title' => 'Parnica',
            'internal_number' => '2026/002',
            'kind' => 'civil',
            'status' => 'active',
            'office_position' => 'plaintiff',
            'billing_method' => 'tariff',
            'dispute_value_cents' => 500000,
        ]);
        $event = CourtEvent::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'type' => 'hearing',
            'title' => 'Ročište',
            'starts_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->post(route('organization.calendar.complete', [$org->slug, $event->id]))
            ->assertRedirect()
            ->assertSessionHas('hearing_offer', $event->id);

        $this->actingAs($user)
            ->post(route('organization.calendar.charge', [$org->slug, $event->id]), [
                'tariff_action' => 'rociste',
                'audience' => 'client',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $charge = TariffCharge::query()->first();
        $this->assertSame($event->id, $charge->court_event_id);
        $this->assertSame(100, $charge->points);
        $this->assertStringContainsString('Zastupanje na ročištu', $charge->description);

        $this->actingAs($user)
            ->post(route('organization.calendar.charge', [$org->slug, $event->id]), [
                'tariff_action' => 'rociste',
                'audience' => 'client',
            ])
            ->assertRedirect();

        $this->assertSame(1, TariffCharge::query()->count());
    }

    public function test_archive_requires_an_outcome(): void
    {
        [$user, $org] = $this->office();
        $matter = Matter::query()->create([
            'organization_id' => $org->id,
            'title' => 'Parnica',
            'internal_number' => '2026/003',
            'kind' => 'civil',
            'status' => 'active',
            'office_position' => 'plaintiff',
            'billing_method' => 'hourly',
        ]);

        $this->actingAs($user)
            ->delete(route('organization.matters.destroy', [$org->slug, $matter->id]))
            ->assertSessionHasErrors('outcome');

        $this->actingAs($user)
            ->delete(route('organization.matters.destroy', [$org->slug, $matter->id]), [
                'outcome' => 'settlement',
            ])
            ->assertRedirect();

        $fresh = $matter->fresh();
        $this->assertSame('archived', $fresh->status->value);
        $this->assertSame('settlement', $fresh->outcome->value);
        $this->assertSame('archived', $fresh->phase()->value);
    }

    public function test_pause_overrides_the_calculated_phase_and_saving_keeps_the_outcome(): void
    {
        [$user, $org] = $this->office();
        $matter = Matter::query()->create([
            'organization_id' => $org->id,
            'title' => 'Parnica',
            'internal_number' => '2026/004',
            'kind' => 'civil',
            'status' => 'archived',
            'outcome' => 'granted',
            'office_position' => 'plaintiff',
            'billing_method' => 'hourly',
        ]);

        $this->actingAs($user)
            ->put(route('organization.matters.update', [$org->slug, $matter->id]), [
                'title' => 'Parnica',
                'kind' => 'civil',
                'office_position' => 'plaintiff',
                'billing_method' => 'hourly',
            ])
            ->assertRedirect();

        $matter->refresh();
        $this->assertSame('archived', $matter->status->value);
        $this->assertSame('granted', $matter->outcome->value);

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id]))
            ->assertOk()
            ->assertSee('Vrati u rad')
            ->assertDontSee('name="status"', false);

        $this->actingAs($user)
            ->post(route('organization.matters.reopen', [$org->slug, $matter->id]))
            ->assertRedirect();

        $matter->refresh();
        $this->assertSame('active', $matter->status->value);
        $this->assertNull($matter->outcome);
        $this->assertSame('new', $matter->phase()->value);
        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id]))
            ->assertOk()
            ->assertSee('data-obrazac-provjera', false)
            ->assertSee('Pauziraj')
            ->assertSee('Arhiviraj')
            ->assertSee('predmet-akcije', false);

        $this->actingAs($user)
            ->post(route('organization.matters.pause', [$org->slug, $matter->id]))
            ->assertRedirect();

        $matter->refresh();
        $this->assertSame('paused', $matter->phase()->value);
        $this->actingAs($user)
            ->get(route('organization.matters.show', [$org->slug, $matter->id]))
            ->assertOk()
            ->assertSee('Nastavi')
            ->assertSee('Pauziran')
            ->assertDontSee('Pauziraj');

        $this->actingAs($user)
            ->post(route('organization.matters.pause', [$org->slug, $matter->id]))
            ->assertRedirect();

        $this->assertSame('new', $matter->fresh()->phase()->value);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function office(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ured',
            'slug' => 'ured-katalog',
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
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Novi predmet',
            'kind' => 'civil',
            'status' => 'active',
            'office_position' => 'plaintiff',
            'billing_method' => 'hourly',
            'client_name' => 'Klijent',
            'client_kind' => 'person',
            'client_oib' => '12345678903',
        ], $extra);
    }
}
