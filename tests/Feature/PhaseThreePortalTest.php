<?php

namespace Tests\Feature;

use App\Enums\EInvoiceStatus;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\ClientAccount;
use App\Models\CourtEvent;
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\MatterParty;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Party;
use App\Models\TimelineEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseThreePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_portal_hides_other_matters_and_internal_notes(): void
    {
        Storage::fake('local');
        [$user, $org] = $this->office('standard');
        $party = $this->party($org, 'company');
        $own = $this->matter($org, $user, '2026/001', 'Moj predmet');
        $other = $this->matter($org, $user, '2026/002', 'Tudi predmet');
        $this->link($own, $party);

        TimelineEntry::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $own->id,
            'type' => 'action',
            'body' => 'Tajna biljeska',
            'occurred_at' => now(),
            'visible_to_client' => false,
        ]);
        TimelineEntry::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $own->id,
            'type' => 'letter',
            'body' => 'Obavijest klijentu',
            'occurred_at' => now(),
            'visible_to_client' => true,
        ]);
        CourtEvent::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $own->id,
            'type' => 'hearing',
            'title' => 'Ročište',
            'starts_at' => now()->addDay(),
            'notes' => 'Interna biljeska rocista',
        ]);
        Storage::disk('local')->put('spis/podijeljeno.txt', 'sadrzaj');
        $shared = MatterDocument::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $own->id,
            'original_name' => 'podijeljeno.txt',
            'path' => 'spis/podijeljeno.txt',
            'size_bytes' => 7,
            'shared_with_client' => true,
        ]);
        $hidden = MatterDocument::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $own->id,
            'original_name' => 'interno.txt',
            'path' => 'spis/podijeljeno.txt',
            'size_bytes' => 7,
            'shared_with_client' => false,
        ]);
        $invoice = Invoice::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $own->id,
            'party_id' => $party->id,
            'number' => '2026-001',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'buyer_name' => $party->name,
            'buyer_oib' => $party->oib,
            'total_cents' => 1000,
        ]);

        $client = ClientAccount::query()->create([
            'organization_id' => $org->id,
            'party_id' => $party->id,
            'name' => $party->name,
            'email' => 'klijent@law.test',
            'password' => 'tajna-lozinka',
        ]);

        $this->actingAs($client, 'client')
            ->get(route('portal.home', $org->slug))
            ->assertOk()
            ->assertSee('Moj predmet')
            ->assertDontSee('Tudi predmet');

        $this->actingAs($client, 'client')
            ->get(route('portal.matters.show', [$org->slug, $other->id]))
            ->assertNotFound();

        $this->actingAs($client, 'client')
            ->get(route('portal.matters.show', [$org->slug, $own->id]))
            ->assertOk()
            ->assertSee('Obavijest klijentu')
            ->assertSee('Ročište')
            ->assertDontSee('Tajna biljeska')
            ->assertDontSee('Interna biljeska rocista');

        $this->actingAs($client, 'client')
            ->get(route('portal.invoices.pdf', [$org->slug, $invoice->id]))
            ->assertOk();

        $this->actingAs($client, 'client')
            ->get(route('portal.documents.download', [$org->slug, $shared->id]))
            ->assertOk();

        $this->actingAs($client, 'client')
            ->get(route('portal.documents.download', [$org->slug, $hidden->id]))
            ->assertNotFound();
    }

    public function test_calendar_exports_office_events_and_imports_an_external_feed_once(): void
    {
        [$user, $org] = $this->office('premium');
        CourtEvent::query()->create([
            'organization_id' => $org->id,
            'type' => 'hearing',
            'title' => 'Glavna rasprava',
            'starts_at' => now()->addDay(),
            'source' => 'office',
        ]);

        $this->actingAs($user)
            ->get(route('organization.calendar.export', $org->slug))
            ->assertOk()
            ->assertHeader('content-type', 'text/calendar; charset=utf-8')
            ->assertSee('Glavna rasprava');

        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:ext-1@test\r\nDTSTART:20261020T100000Z\r\nSUMMARY:Sastanak izvana\r\nEND:VEVENT\r\nEND:VCALENDAR";
        Http::fake(['https://kalendar.test/feed.ics' => Http::response($ics)]);

        $this->actingAs($user)->post(route('organization.calendar.import', $org->slug), [
            'feed_url' => 'https://kalendar.test/feed.ics',
        ])->assertRedirect();
        $this->actingAs($user)->post(route('organization.calendar.import', $org->slug), [
            'feed_url' => 'https://kalendar.test/feed.ics',
        ])->assertRedirect();

        $this->assertSame(1, CourtEvent::query()->where('external_uid', 'ext-1@test')->count());
    }

    public function test_mail_intake_files_a_message_on_the_matter(): void
    {
        [$user, $org] = $this->office('premium');
        $this->matter($org, $user, '2026/001', 'Predmet');
        $org->forceFill(['mail_intake_token' => str_repeat('a', 40)])->save();

        $this->postJson('/api/posta/'.str_repeat('a', 40), [
            'from' => 'sud@example.test',
            'subject' => 'Dopis [2026/001]',
            'body' => 'Tekst poziva.',
        ])->assertOk();

        $this->assertDatabaseHas('timeline_entries', [
            'type' => 'email',
            'visible_to_client' => false,
        ]);
        $this->assertStringContainsString('Tekst poziva.', TimelineEntry::query()->first()->body);
    }

    public function test_court_register_fills_a_company_and_refuses_a_person(): void
    {
        [$user, $org] = $this->office('standard');
        $company = $this->party($org, 'company');
        $person = $this->party($org, 'person', '10987654326');
        config([
            'services.sudreg.client_id' => 'klijent',
            'services.sudreg.client_secret' => 'tajna',
        ]);
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'tok']),
            '*/javni/detalji_subjekta*' => Http::response([
                'mbs' => 81234567,
                'oib' => $company->oib,
                'tvrtke' => [['naziv' => 'Nova tvrtka d.o.o.']],
                'sjedista' => [['ulica' => 'Ilica', 'kucni_broj' => '1', 'naziv_naselja' => 'Zagreb']],
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('organization.parties.registry', [$org->slug, $company->id]))
            ->assertRedirect();
        $this->assertSame('Nova tvrtka d.o.o.', $company->fresh()->name);
        $this->assertSame('Zagreb', $company->fresh()->city);

        $this->actingAs($user)
            ->post(route('organization.parties.registry', [$org->slug, $person->id]))
            ->assertSessionHasErrors('oib');
    }

    public function test_e_invoice_is_prepared_and_can_be_sent(): void
    {
        Storage::fake('local');
        [$user, $org] = $this->office('premium');
        $party = $this->party($org, 'company');
        $matter = $this->matter($org, $user, '2026/001', 'Predmet');
        $invoice = Invoice::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'number' => '2026-010',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'buyer_name' => 'Kupac d.o.o.',
            'buyer_oib' => '12345678903',
            'subtotal_cents' => 10000,
            'vat_cents' => 2500,
            'total_cents' => 12500,
        ]);

        $this->actingAs($user)
            ->post(route('organization.invoices.einvoice', [$org->slug, $invoice->id]))
            ->assertRedirect();
        $invoice->refresh();
        $this->assertSame(EInvoiceStatus::Prepared, $invoice->e_invoice_status);
        $this->assertStringContainsString('12345678903', Storage::disk('local')->get($invoice->e_invoice_path));

        config(['services.moj_eracun.url' => 'https://eracun.test/send', 'services.moj_eracun.username' => 'u', 'services.moj_eracun.password' => 'p']);
        Http::fake(['https://eracun.test/send' => Http::response('ok', 200)]);

        $this->actingAs($user)
            ->post(route('organization.invoices.einvoice', [$org->slug, $invoice->id]))
            ->assertRedirect();
        $this->assertSame(EInvoiceStatus::Sent, $invoice->fresh()->e_invoice_status);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function office(string $plan): array
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

    private function matter(Organization $organization, User $user, string $number, string $title): Matter
    {
        $matter = Matter::query()->create([
            'organization_id' => $organization->id,
            'title' => $title,
            'internal_number' => $number,
            'kind' => 'civil',
            'status' => 'active',
            'billing_method' => 'hourly',
            'created_by_user_id' => $user->id,
        ]);
        $matter->assignees()->sync([$user->id]);

        return $matter;
    }

    private function party(Organization $organization, string $kind, string $oib = '12345678903'): Party
    {
        return Party::query()->create([
            'organization_id' => $organization->id,
            'kind' => $kind,
            'name' => $kind === 'company' ? 'Stara tvrtka d.o.o.' : 'Ivan Osoba',
            'oib' => $oib,
        ]);
    }

    private function link(Matter $matter, Party $party): void
    {
        MatterParty::query()->create([
            'organization_id' => $matter->organization_id,
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'role' => 'client',
            'side' => 'client',
        ]);
    }
}
