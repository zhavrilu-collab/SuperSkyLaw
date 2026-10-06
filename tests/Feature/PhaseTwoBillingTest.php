<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\TimeEntryStatus;
use App\Mail\InvoiceOverdueMail;
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Party;
use App\Models\TariffAction;
use App\Models\TariffCharge;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TariffCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseTwoBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tariff_bands_and_caps_follow_the_hok_catalog(): void
    {
        $catalog = app(TariffCatalog::class);
        $version = $catalog->current();
        $claim = TariffAction::query()->where('code', 'tuzba')->firstOrFail();
        $submission = TariffAction::query()->where('code', 'podnesak')->firstOrFail();

        $this->assertSame(75, $catalog->bandPoints($version, 100_000));
        $this->assertSame(528, $catalog->bandPoints($version, 7_000_000));
        $this->assertSame(15_000, $catalog->quote($claim, 100_000)['amount_cents']);
        $this->assertSame(100, $catalog->quote($submission, 7_000_000)['points']);
    }

    public function test_client_fee_is_invoiced_and_opposing_fee_stays_on_the_cost_bill(): void
    {
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user, 100_000);
        $party = $this->party($org);
        $action = TariffAction::query()->where('code', 'tuzba')->firstOrFail();

        $this->actingAs($user)->post(route('organization.tariff.store', $org->slug), [
            'matter_id' => $matter->id,
            'tariff_action_id' => $action->id,
            'audience' => 'client',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('organization.tariff.store', $org->slug), [
            'matter_id' => $matter->id,
            'tariff_action_id' => $action->id,
            'audience' => 'opposing',
        ])->assertRedirect();

        $client = TariffCharge::query()->where('audience', 'client')->firstOrFail();
        $opposing = TariffCharge::query()->where('audience', 'opposing')->firstOrFail();

        $this->actingAs($user)->post(route('organization.expenses.store', $org->slug), [
            'matter_id' => $matter->id,
            'category' => 'court_fee',
            'description' => 'Pristojba',
            'amount' => 20,
            'bill_to' => 'opposing',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('organization.invoices.store', $org->slug), [
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'tariff_charge_ids' => [$client->id, $opposing->id],
        ])->assertRedirect();

        $this->assertNotNull($client->fresh()->invoice_id);
        $this->assertNull($opposing->fresh()->invoice_id);
        $this->assertSame(15_000, Invoice::query()->first()->subtotal_cents);

        $this->actingAs($user)
            ->get(route('organization.cost-bills.pdf', [$org->slug, $matter->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_basic_plan_has_no_tariff_and_time_is_approved_immediately(): void
    {
        [$user, $org] = $this->office('basic');
        $matter = $this->matter($org, $user, null);

        $this->actingAs($user)
            ->get(route('organization.tariff.index', $org->slug))
            ->assertForbidden();

        $this->actingAs($user)->post(route('organization.time.store', $org->slug), [
            'matter_id' => $matter->id,
            'description' => 'Sastanak',
            'minutes' => 30,
        ])->assertRedirect();

        $this->assertSame(TimeEntryStatus::Approved, TimeEntry::query()->first()->status);
    }

    public function test_written_off_time_stays_visible_and_is_not_invoiced(): void
    {
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user, null);
        $party = $this->party($org);

        $this->actingAs($user)->post(route('organization.time.store', $org->slug), [
            'matter_id' => $matter->id,
            'description' => 'Interni sastanak',
            'minutes' => 45,
        ])->assertRedirect();

        $entry = TimeEntry::query()->firstOrFail();
        $this->assertSame(TimeEntryStatus::Draft, $entry->status);

        $this->actingAs($user)
            ->post(route('organization.time.write-off', [$org->slug, $entry->id]))
            ->assertRedirect();

        $this->actingAs($user)->post(route('organization.invoices.store', $org->slug), [
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'time_entry_ids' => [$entry->id],
        ])->assertSessionHasErrors('time_entry_ids');

        $this->actingAs($user)
            ->get(route('organization.time.index', $org->slug))
            ->assertOk()
            ->assertSee('Otpis');
    }

    public function test_template_merges_party_and_document_versions_are_kept(): void
    {
        Storage::fake('local');
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user, null);
        $party = $this->party($org);
        $templateId = \App\Models\DocumentTemplate::query()->where('code', 'punomoc')->value('id');

        $this->actingAs($user)->post(route('organization.templates.generate', $org->slug), [
            'template_id' => $templateId,
            'matter_id' => $matter->id,
            'party_id' => $party->id,
        ])->assertRedirect();

        $document = MatterDocument::query()->firstOrFail();
        $this->assertStringContainsString('Ivan Klijent', Storage::disk('local')->get($document->path));

        $this->actingAs($user)->post(route('organization.documents.version', [$org->slug, $document->id]), [
            'file' => UploadedFile::fake()->create('punomoc-v2.txt', 10, 'text/plain'),
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame(2, $document->version);
        Storage::disk('local')->assertExists($document->versions()->first()->path);
    }

    public function test_overdue_invoice_sends_one_reminder(): void
    {
        Mail::fake();
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user, null);
        $party = $this->party($org, 'klijent@example.test');

        Invoice::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'number' => '2026-001',
            'issue_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'status' => 'unpaid',
            'subtotal_cents' => 10000,
            'vat_cents' => 2500,
            'total_cents' => 12500,
            'buyer_name' => $party->name,
            'buyer_oib' => $party->oib,
        ]);

        $this->artisan('legal:send-invoice-reminders')->assertSuccessful();
        $this->artisan('legal:send-invoice-reminders')->assertSuccessful();

        Mail::assertSent(InvoiceOverdueMail::class, 1);
        $this->assertDatabaseCount('invoice_reminders', 2);
    }

    public function test_report_shows_utilization_and_aging(): void
    {
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user, null);
        $party = $this->party($org);

        TimeEntry::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'user_id' => $user->id,
            'description' => 'Žalba',
            'minutes' => 60,
            'hourly_rate_cents' => 10000,
            'status' => TimeEntryStatus::Approved,
            'created_at' => '2026-10-15 10:00:00',
            'updated_at' => '2026-10-15 10:00:00',
        ]);

        Invoice::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'number' => '2026-009',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-20',
            'status' => 'overdue',
            'subtotal_cents' => 4000,
            'vat_cents' => 0,
            'total_cents' => 4000,
            'paid_cents' => 0,
            'buyer_name' => $party->name,
        ]);

        $this->travelTo('2026-10-15 12:00:00');

        $this->actingAs($user)
            ->get(route('organization.reports.index', ['slug' => $org->slug, 'month' => '2026-10']))
            ->assertOk()
            ->assertSee('0,6 %')
            ->assertSee('40,00');
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

    private function matter(Organization $organization, User $user, ?int $disputeValue): Matter
    {
        $matter = Matter::query()->create([
            'organization_id' => $organization->id,
            'title' => 'P-1',
            'internal_number' => '2026/001',
            'kind' => 'civil',
            'status' => 'active',
            'dispute_value_cents' => $disputeValue,
            'billing_method' => 'tariff',
            'created_by_user_id' => $user->id,
        ]);
        $matter->assignees()->sync([$user->id]);

        return $matter;
    }

    private function party(Organization $organization, string $email = 'ivan@example.test'): Party
    {
        return Party::query()->create([
            'organization_id' => $organization->id,
            'kind' => 'person',
            'name' => 'Ivan Klijent',
            'oib' => '12345678903',
            'email' => $email,
        ]);
    }
}
