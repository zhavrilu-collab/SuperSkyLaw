<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PartySide;
use App\Enums\SignatureStatus;
use App\Enums\SmsStatus;
use App\Models\CourtEvent;
use App\Models\LimitationEstimate;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\MatterParty;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Party;
use App\Models\User;
use App\Services\ConflictCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseFourComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ethical_wall_hides_the_matter_and_redacts_the_conflict(): void
    {
        [$owner, $organization] = $this->office();
        $lawyer = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $lawyer->id,
            'role' => OrganizationRole::Lawyer,
        ]);
        $matter = $this->matter($organization, $owner, '2026/001');
        $matter->assignees()->sync([$owner->id, $lawyer->id]);
        $party = Party::query()->create([
            'organization_id' => $organization->id,
            'kind' => 'person',
            'name' => 'Ivan Klijent',
            'oib' => '12345678903',
        ]);
        MatterParty::query()->create([
            'organization_id' => $organization->id,
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'role' => 'client',
            'side' => 'client',
        ]);

        $this->actingAs($lawyer)
            ->get(route('organization.matters.show', [$organization->slug, $matter->id]))
            ->assertOk();

        $this->actingAs($owner)->post(route('organization.matters.walls.store', [$organization->slug, $matter->id]), [
            'user_id' => $lawyer->id,
            'reason' => 'Druga strana predmeta',
        ])->assertRedirect();

        $this->actingAs($lawyer)
            ->get(route('organization.matters.show', [$organization->slug, $matter->id]))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('organization.matters.show', [$organization->slug, $matter->id]))
            ->assertOk()
            ->assertSee('Druga strana predmeta');

        $this->actingAs($owner)->post(route('organization.matters.walls.store', [$organization->slug, $matter->id]), [
            'user_id' => $owner->id,
            'reason' => 'Sebe',
        ])->assertSessionHasErrors('user_id');

        $this->actingAs($lawyer);
        $evaluation = app(ConflictCheckService::class)->evaluate([[
            'name' => 'Ivan Klijent',
            'oib' => '12345678903',
            'side' => PartySide::Opposing,
        ]]);
        $this->assertSame('Isti OIB je na predmetu iza etičkog zida.', $evaluation['matches'][0]['reason']);
        $this->assertNull($evaluation['matches'][0]['matter_number']);
        $this->assertDatabaseHas('ethical_walls', ['matter_id' => $matter->id, 'user_id' => $lawyer->id]);
    }

    public function test_spnft_checklist_can_be_confirmed_on_a_commercial_matter(): void
    {
        [$owner, $organization] = $this->office();
        $matter = $this->matter($organization, $owner, '2026/002', 'commercial');
        $matter->forceFill(['spnft_required' => true])->save();

        $this->actingAs($owner)
            ->get(route('organization.matters.show', [$organization->slug, $matter->id]))
            ->assertOk()
            ->assertSee('Identitet stranke je utvrđen');

        $this->actingAs($owner)->post(route('organization.matters.spnft.toggle', [$organization->slug, $matter->id]), [
            'item' => 'identity',
        ])->assertRedirect();

        $this->assertDatabaseHas('spnft_checks', [
            'matter_id' => $matter->id,
            'item' => 'identity',
            'completed_by_user_id' => $owner->id,
        ]);
    }

    public function test_trust_account_rejects_a_withdrawal_above_the_client_balance(): void
    {
        [$owner, $organization] = $this->office();
        $organization->forceFill(['trust_iban' => 'HR1223600001500000001', 'iban' => 'HR1210010051863000160'])->save();
        $matter = $this->matter($organization, $owner, '2026/003');

        $this->actingAs($owner)->post(route('organization.trust.store', $organization->slug), [
            'matter_id' => $matter->id,
            'direction' => 'in',
            'amount' => '100.00',
            'purpose' => 'Predujam',
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('organization.trust.store', $organization->slug), [
            'matter_id' => $matter->id,
            'direction' => 'out',
            'amount' => '40.00',
            'purpose' => 'Sudska pristojba',
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('organization.trust.store', $organization->slug), [
            'matter_id' => $matter->id,
            'direction' => 'out',
            'amount' => '70.00',
            'purpose' => 'Previše',
        ])->assertSessionHasErrors('amount');

        $this->actingAs($owner)
            ->get(route('organization.trust.index', $organization->slug))
            ->assertOk()
            ->assertSee('60,00 EUR')
            ->assertSee('HR1223600001500000001')
            ->assertDontSee('HR1210010051863000160');
    }

    public function test_limitation_help_states_it_is_not_legal_advice(): void
    {
        [$owner, $organization] = $this->office();
        $matter = $this->matter($organization, $owner, '2026/004');

        $this->actingAs($owner)->post(route('organization.matters.limitation.store', [$organization->slug, $matter->id]), [
            'basis' => 'general_5',
            'starts_on' => '2020-01-15',
        ])->assertRedirect();

        $this->assertSame('2025-01-15', LimitationEstimate::query()->first()->suggested_on->toDateString());
        $this->assertDatabaseHas('court_events', [
            'matter_id' => $matter->id,
            'type' => 'limitation',
        ]);
        $this->actingAs($owner)
            ->get(route('organization.matters.show', [$organization->slug, $matter->id, 'tab' => 'rokovi']))
            ->assertOk()
            ->assertSee('nije pravni savjet');
    }

    public function test_e_oglasna_is_a_manual_link_without_a_remote_call(): void
    {
        Http::fake();
        [$owner, $organization] = $this->office();

        $this->actingAs($owner)->post(route('organization.calendar.store', $organization->slug), [
            'title' => 'Objava presude',
            'type' => 'hearing',
            'starts_at' => '2026-10-20 10:00:00',
            'e_oglasna_url' => 'https://e-oglasna.pravosudje.hr/notice/1',
        ])->assertRedirect();

        Http::assertNothingSent();
        $this->actingAs($owner)
            ->get(route('organization.calendar.index', $organization->slug))
            ->assertOk()
            ->assertSee('https://e-oglasna.pravosudje.hr/notice/1');
    }

    public function test_qualified_signature_is_not_marked_signed_without_provider_confirmation(): void
    {
        Storage::fake('local');
        [$owner, $organization] = $this->office('basic');
        $matter = $this->matter($organization, $owner, '2026/005');
        Storage::disk('local')->put('spis/ugovor.txt', 'tekst');
        $document = MatterDocument::query()->create([
            'organization_id' => $organization->id,
            'matter_id' => $matter->id,
            'original_name' => 'ugovor.txt',
            'path' => 'spis/ugovor.txt',
            'size_bytes' => 5,
        ]);

        $this->actingAs($owner)
            ->post(route('organization.documents.sign', [$organization->slug, $document->id]))
            ->assertForbidden();

        $organization->forceFill(['plan' => 'premium'])->save();
        $this->actingAs($owner)
            ->post(route('organization.documents.sign', [$organization->slug, $document->id]))
            ->assertRedirect();
        $this->assertSame(SignatureStatus::Prepared, $document->fresh()->signatureRequest->status);

        config(['services.esign.url' => 'https://potpis.test/sign', 'services.esign.username' => 'u', 'services.esign.password' => 'p']);
        Http::fake([
            'https://potpis.test/*' => Http::sequence()
                ->push(['accepted' => true], 200)
                ->push(['signed' => true, 'reference' => 'q-1'], 200),
        ]);
        $this->actingAs($owner)
            ->post(route('organization.documents.sign', [$organization->slug, $document->id]))
            ->assertRedirect();
        $this->assertSame(SignatureStatus::Sent, $document->fresh()->signatureRequest->status);

        $this->actingAs($owner)
            ->post(route('organization.documents.sign', [$organization->slug, $document->id]))
            ->assertRedirect();
        $this->assertSame(SignatureStatus::Signed, $document->fresh()->signatureRequest->status);
    }

    public function test_sms_is_sent_only_with_consent_and_a_provider(): void
    {
        Http::fake();
        [$owner, $organization] = $this->office();
        $matter = $this->matter($organization, $owner, '2026/006');
        $party = Party::query()->create([
            'organization_id' => $organization->id,
            'kind' => 'person',
            'name' => 'Ana Klijent',
            'phone' => '+38591111222',
        ]);
        MatterParty::query()->create([
            'organization_id' => $organization->id,
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'role' => 'client',
            'side' => 'client',
        ]);
        $event = CourtEvent::query()->create([
            'organization_id' => $organization->id,
            'matter_id' => $matter->id,
            'type' => 'hearing',
            'title' => 'Ročište',
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)->post(route('organization.calendar.sms', [$organization->slug, $event->id]), [
            'party_id' => $party->id,
        ])->assertRedirect();
        $this->assertSame(SmsStatus::SkippedNoConsent, $party->smsMessages()->first()->status);
        Http::assertNothingSent();

        $party->forceFill(['sms_consent_at' => now()])->save();
        $this->actingAs($owner)->post(route('organization.calendar.sms', [$organization->slug, $event->id]), [
            'party_id' => $party->id,
        ])->assertRedirect();
        $this->assertSame(SmsStatus::SkippedNoProvider, $party->smsMessages()->latest('id')->first()->status);
        Http::assertNothingSent();

        config(['services.sms.url' => 'https://sms.test/send', 'services.sms.token' => 'tok', 'services.sms.cost_cents' => 8]);
        Http::fake(['https://sms.test/send' => Http::response('ok', 200)]);
        $this->actingAs($owner)->post(route('organization.calendar.sms', [$organization->slug, $event->id]), [
            'party_id' => $party->id,
        ])->assertRedirect();
        $sent = $party->smsMessages()->latest('id')->first();
        $this->assertSame(SmsStatus::Sent, $sent->status);
        $this->assertSame(8, $sent->cost_cents);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function office(string $plan = 'standard'): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ured Test',
            'slug' => 'ured-test',
            'status' => OrganizationStatus::Active,
            'plan' => $plan,
            'oib' => '12345678903',
            'city' => 'Zagreb',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$user, $organization];
    }

    private function matter(Organization $organization, User $user, string $number, string $kind = 'civil'): Matter
    {
        $matter = Matter::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Predmet',
            'internal_number' => $number,
            'kind' => $kind,
            'status' => 'active',
            'billing_method' => 'hourly',
            'created_by_user_id' => $user->id,
        ]);
        $matter->assignees()->sync([$user->id]);

        return $matter;
    }
}
