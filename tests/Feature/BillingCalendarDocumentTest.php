<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\TimeEntryStatus;
use App\Mail\DeadlineReminderMail;
use App\Models\CourtEvent;
use App\Models\Expense;
use App\Models\Matter;
use App\Models\OfficeNotification;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Party;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BillingCalendarDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_deadline_reminder_sends_mail_and_in_app_notice(): void
    {
        Mail::fake();
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user);

        $event = CourtEvent::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'type' => 'appeal_deadline',
            'title' => 'Rok za žalbu',
            'starts_at' => now()->addDays(2),
            'responsible_user_id' => $user->id,
            'is_preclusive' => true,
        ]);
        $event->forceFill(['created_at' => now()->subDays(4)])->save();

        $this->artisan('legal:send-deadline-reminders')->assertSuccessful();

        $this->assertDatabaseHas('office_notifications', [
            'user_id' => $user->id,
            'title' => 'Rok: Rok za žalbu',
        ]);
        Mail::assertSent(DeadlineReminderMail::class);
        $this->assertSame(1, OfficeNotification::query()->count());
    }

    public function test_approved_time_and_expense_produce_invoice_pdf(): void
    {
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user);
        $party = Party::query()->create([
            'organization_id' => $org->id,
            'kind' => 'person',
            'name' => 'Ivan Klijent',
            'oib' => '12345678903',
        ]);

        $entry = TimeEntry::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'user_id' => $user->id,
            'description' => 'Izrada žalbe',
            'minutes' => 60,
            'hourly_rate_cents' => 10000,
            'status' => TimeEntryStatus::Approved,
        ]);

        Expense::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'category' => 'postage',
            'description' => 'Poštarina',
            'amount_cents' => 500,
        ]);

        $this->actingAs($user)->post(route('organization.invoices.store', $org->slug), [
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'time_entry_ids' => [$entry->id],
            'expense_ids' => [Expense::query()->first()->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'buyer_oib' => '12345678903',
            'subtotal_cents' => 10500,
        ]);

        $invoiceId = \App\Models\Invoice::query()->first()->id;
        $this->actingAs($user)
            ->get(route('organization.invoices.pdf', [$org->slug, $invoiceId]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_document_upload_is_private_and_audited(): void
    {
        Storage::fake('local');
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user);

        $this->actingAs($user)->post(route('organization.documents.store', $org->slug), [
            'matter_id' => $matter->id,
            'folder' => 'Podnesci',
            'file' => UploadedFile::fake()->create('zalba.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $document = \App\Models\MatterDocument::query()->first();
        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->path);

        $this->actingAs($user)
            ->get(route('organization.documents.download', [$org->slug, $document->id]))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => \App\Models\MatterDocument::class,
            'action' => 'view',
        ]);
    }

    public function test_matter_document_upload_without_a_folder_uses_the_kind(): void
    {
        Storage::fake('local');
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user);

        $this->actingAs($user)->post(route('organization.documents.store', $org->slug), [
            'matter_id' => $matter->id,
            'kind' => 'brief',
            'return_to' => 'matter',
            'file' => UploadedFile::fake()->create('tuzba.pdf', 20, 'application/pdf'),
        ])->assertRedirect(route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'dokumenti']));

        $document = \App\Models\MatterDocument::query()->first();
        $this->assertSame('Podnesci', $document->folder);
        $this->assertSame('brief', $document->kind->value);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function office(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ured Test',
            'slug' => 'ured-test',
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

    private function matter(Organization $organization, User $user): Matter
    {
        $matter = Matter::query()->create([
            'organization_id' => $organization->id,
            'title' => 'P-1',
            'internal_number' => '2026/001',
            'kind' => 'civil',
            'status' => 'active',
            'billing_method' => 'hourly',
            'hourly_rate_cents' => 10000,
            'created_by_user_id' => $user->id,
        ]);
        $matter->assignees()->sync([$user->id]);

        return $matter;
    }
}
