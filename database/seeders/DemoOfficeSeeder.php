<?php

namespace Database\Seeders;

use App\Enums\BillingMethod;
use App\Enums\ConflictResult;
use App\Enums\CourtEventType;
use App\Enums\EInvoiceStatus;
use App\Enums\ExpenseCategory;
use App\Enums\FeeAudience;
use App\Enums\InvoiceStatus;
use App\Enums\MatterKind;
use App\Enums\MatterOutcome;
use App\Enums\MatterPartyRole;
use App\Enums\MatterStatus;
use App\Enums\OfficePosition;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PartyKind;
use App\Enums\PartySide;
use App\Enums\TimeEntryStatus;
use App\Enums\TimelineEntryType;
use App\Enums\TrustDirection;
use App\Jobs\NotifyAdminConsoleJob;
use App\Models\ClientAccount;
use App\Models\ConflictCheck;
use App\Models\Court;
use App\Models\CourtEvent;
use App\Models\DisputeCategory;
use App\Models\DeadlineReminder;
use App\Models\EthicalWall;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoiceReminder;
use App\Models\LimitationEstimate;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\MatterParty;
use App\Models\OfficeNotification;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Party;
use App\Models\Payment;
use App\Models\SpnftCheck;
use App\Models\TariffAction;
use App\Models\TariffCharge;
use App\Models\TimeEntry;
use App\Models\TimelineEntry;
use App\Models\TrustMovement;
use App\Models\User;
use App\Services\TariffCatalog;
use App\Support\CroatianOib;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Odobreni demo ured za isprobavanje uloga i scenarija predmeta.
 * Lozinka svih računa osoblja (i portala klijenta): Demo2026!
 */
class DemoOfficeSeeder extends Seeder
{
    public const PASSWORD = 'Demo2026!';

    public const SLUG = 'demo-ured-kovac';

    public function run(): void
    {
        app(TariffCatalog::class)->install();
        $this->call([
            CourtSeeder::class,
            DisputeCategorySeeder::class,
        ]);

        DB::transaction(function (): void {
            $org = $this->organization();
            $users = $this->users($org);
            $this->wipeOfficeData($org);
            $parties = $this->parties($org);
            $matters = $this->matters($org, $users, $parties);
            $this->portalClient($org, $parties['marina']);
            $this->sharedDocument($org, $matters['DU-2026-001'], $users['ivana']);
            $this->notification($org, $users['petra'], $matters['DU-2026-003']);
        });

        $organization = Organization::query()->where('slug', self::SLUG)->first();
        if ($organization !== null) {
            try {
                NotifyAdminConsoleJob::dispatchSync($organization->id);
            } catch (\Throwable $exception) {
                $this->command?->warn('Admin konzola nije ažurirana: '.$exception->getMessage());
            }
        }

        $this->command?->info('Demo ured: '.self::SLUG.' / lozinka '.self::PASSWORD);
    }

    private function organization(): Organization
    {
        return Organization::query()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Odvjetnički ured Kovač i partneri (demo)',
                'status' => OrganizationStatus::Active,
                'plan' => 'premium',
                'status_changed_at' => now(),
                'trial_ends_at' => null,
                'email' => 'ured@demo-ured.test',
                'phone' => '+385 1 555 0190',
                'city' => 'Zagreb',
                'address' => 'Ilica 12',
                'oib' => $this->oib('6943518270'),
                'iban' => 'HR1210010051863000160',
                'trust_iban' => 'HR2324020063201412345',
                'theme_color' => 'zelena',
                'theme_style' => 'kreda',
            ],
        );
    }

    /**
     * @return array<string, User>
     */
    private function users(Organization $org): array
    {
        $roster = [
            'petra' => ['Petra Kovač', 'petra.kovac@demo-ured.test', OrganizationRole::Owner],
            'ivana' => ['Ivana Horvat', 'ivana.horvat@demo-ured.test', OrganizationRole::Lawyer],
            'luka' => ['Luka Babić', 'luka.babic@demo-ured.test', OrganizationRole::Lawyer],
            'sara' => ['Sara Novak', 'sara.novak@demo-ured.test', OrganizationRole::Lawyer],
            'tin' => ['Tin Marić', 'tin.maric@demo-ured.test', OrganizationRole::Trainee],
            'maja' => ['Maja Jurić', 'maja.juric@demo-ured.test', OrganizationRole::Secretary],
        ];

        $users = [];
        foreach ($roster as $key => [$name, $email, $role]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => self::PASSWORD,
                    'email_verified_at' => now(),
                ],
            );
            OrganizationUser::query()->updateOrCreate(
                ['organization_id' => $org->id, 'user_id' => $user->id],
                ['role' => $role],
            );
            $users[$key] = $user;
        }

        return $users;
    }

    private function wipeOfficeData(Organization $org): void
    {
        ClientAccount::query()->where('organization_id', $org->id)->delete();
        OfficeNotification::query()->where('organization_id', $org->id)->delete();
        Matter::query()->where('organization_id', $org->id)->delete();
        Party::query()->where('organization_id', $org->id)->delete();
        Storage::disk('local')->deleteDirectory('demo-ured/'.$org->id);
    }

    /**
     * @return array<string, Party>
     */
    private function parties(Organization $org): array
    {
        $defs = [
            'marina' => [PartyKind::Person, 'Marina Božić', 'Zagreb', 'marina.bozic@demo-ured.test', '+385 91 555 0101', true],
            'ivan' => [PartyKind::Person, 'Ivan Knežević', 'Zagreb', null, null, false],
            'klara' => [PartyKind::Person, 'Klara Šimić', 'Samobor', null, null, false],
            'fran' => [PartyKind::Person, 'Fran Lukić', 'Zagreb', null, null, false],
            'davor' => [PartyKind::Person, 'Davor Benić', 'Zagreb', null, null, false],
            'kolar' => [PartyKind::Person, 'Obitelj Kolar', 'Velika Gorica', null, null, false],
            'marko' => [PartyKind::Person, 'Marko Petrić', 'Zagreb', null, '+385 91 555 0108', true],
            'ema' => [PartyKind::Person, 'Ema Jurić', 'Sesvete', null, null, false],
            'rh' => [PartyKind::Company, 'Republika Hrvatska', 'Zagreb', null, null, false],
            'ana' => [PartyKind::Person, 'Ana Marić', 'Zagreb', 'ana.maric@demo-ured.test', null, false],
            'keramika' => [PartyKind::Company, 'Obrt Keramika', 'Zaprešić', null, null, false],
            'hotel' => [PartyKind::Company, 'Hotel Jadran d.o.o.', 'Split', 'pravni@hotel-jadran.demo', null, false],
            'sunce' => [PartyKind::Company, 'Trgovina Sunce d.o.o.', 'Split', null, null, false],
            'val' => [PartyKind::Company, 'Osiguranje Val d.d.', 'Zagreb', null, null, false],
            'banka' => [PartyKind::Company, 'Banka Sjever d.d.', 'Varaždin', 'pravni@banka-sjever.demo', null, false],
            'josip' => [PartyKind::Person, 'Josip Novak', 'Varaždin', null, null, false],
            'stekstil' => [PartyKind::Company, 'Tekstil d.o.o. u stečaju', 'Karlovac', null, null, false],
            'delta' => [PartyKind::Company, 'Projekt Delta d.o.o.', 'Zagreb', null, null, false],
            'grad' => [PartyKind::Company, 'Grad Zagreb', 'Zagreb', null, null, false],
            'medo' => [PartyKind::Company, 'Pekara Medo j.d.o.o.', 'Osijek', null, null, false],
            'porezna' => [PartyKind::Company, 'Porezna uprava', 'Zagreb', null, null, false],
            'elena' => [PartyKind::Person, 'Elena Vuković', 'Zagreb', null, null, false],
            'tomislav' => [PartyKind::Person, 'Tomislav Horvat', 'Zagreb', null, null, false],
            'holding' => [PartyKind::Company, 'Adriatic Holding d.o.o.', 'Rijeka', null, null, false],
            'invest' => [PartyKind::Company, 'Marina Invest d.o.o.', 'Rijeka', null, null, false],
            'nikola_a' => [PartyKind::Person, 'Nikola Radić', 'Pula', null, null, false],
            'nikola_b' => [PartyKind::Person, 'Nikola Radić', 'Rijeka', null, null, false],
            'sindikat' => [PartyKind::Company, 'Sindikat trgovine', 'Zagreb', null, null, false],
            'market' => [PartyKind::Company, 'Lanac Market d.d.', 'Zagreb', null, null, false],
            'iva' => [PartyKind::Person, 'Iva Špoljar', 'Sisak', null, null, false],
            'mobbing' => [PartyKind::Person, 'Helena Grgić', 'Zagreb', null, null, false],
            'agencija' => [PartyKind::Company, 'Agencija Sjever d.o.o.', 'Zagreb', null, null, false],
        ];

        $parties = [];
        $index = 0;
        foreach ($defs as $key => [$kind, $name, $city, $email, $phone, $sms]) {
            $index++;
            $parties[$key] = Party::query()->create([
                'organization_id' => $org->id,
                'kind' => $kind,
                'name' => $name,
                'oib' => $this->oib(str_pad((string) (5100000000 + $index), 10, '0', STR_PAD_LEFT)),
                'city' => $city,
                'address' => 'Ulica demo '.$index,
                'email' => $email,
                'phone' => $phone,
                'sms_consent_at' => $sms ? now()->subMonths(2) : null,
                'contact_person' => $kind === PartyKind::Company ? 'Tajništvo' : null,
            ]);
        }

        return $parties;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Party>  $parties
     * @return array<string, Matter>
     */
    private function matters(Organization $org, array $users, array $parties): array
    {
        $created = [];
        foreach ($this->matterSpecs() as $spec) {
            $matter = Matter::query()->create([
                'organization_id' => $org->id,
                'title' => $spec['title'],
                'internal_number' => $spec['number'],
                'kind' => $spec['kind'],
                'status' => $spec['status'],
                'office_position' => match ($spec['client_role']) {
                    MatterPartyRole::Plaintiff => OfficePosition::Plaintiff,
                    MatterPartyRole::Defendant => OfficePosition::Defendant,
                    MatterPartyRole::Creditor => OfficePosition::Petitioner,
                    MatterPartyRole::Debtor => OfficePosition::Opposing,
                    default => $spec['kind'] === MatterKind::Criminal ? OfficePosition::Accused : OfficePosition::Petitioner,
                },
                'outcome' => $spec['status'] === MatterStatus::Archived ? MatterOutcome::Granted : null,
                'spnft_required' => $spec['spnft'] ?? false,
                'court_id' => $spec['court'] ? Court::query()->where('name', $spec['court'])->value('id') : null,
                'court_name' => $spec['court'],
                'case_mark' => $spec['mark'],
                'case_number' => $spec['case'],
                'case_year' => $spec['year'],
                'dispute_value_cents' => $spec['value'] ?? null,
                'billing_method' => $spec['billing'],
                'hourly_rate_cents' => $spec['rate'] ?? null,
                'flat_fee_cents' => $spec['flat'] ?? null,
                'success_fee_note' => $spec['success'] ?? null,
                'created_by_user_id' => $users['petra']->id,
            ]);

            $assigneeIds = array_map(fn (string $key): int => $users[$key]->id, $spec['assignees']);
            $matter->assignees()->sync($assigneeIds);

            $this->linkParty($org, $matter, $parties[$spec['client']], $spec['client_role'], PartySide::Client);
            if (isset($spec['opposing'])) {
                $this->linkParty($org, $matter, $parties[$spec['opposing']], $spec['opposing_role'], PartySide::Opposing);
            }

            TimelineEntry::query()->create([
                'organization_id' => $org->id,
                'matter_id' => $matter->id,
                'type' => TimelineEntryType::Action,
                'body' => $spec['note'],
                'occurred_at' => now()->subDays($spec['note_days'] ?? 12),
                'user_id' => $users[$spec['assignees'][0]]->id,
                'visible_to_client' => (bool) ($spec['client_visible'] ?? false),
            ]);

            $created[$spec['number']] = $matter;
        }

        $categoryNames = [
            'DU-2026-001' => 'Naknada štete (materijalna / nematerijalna)',
            'DU-2025-014' => 'Smetanje posjeda',
            'DU-2026-012' => 'Razvod braka i povjeravanje djece',
            'DU-2026-007' => 'Nedopuštenost otkaza ugovora o radu',
            'DU-2026-009' => 'Ovrha na temelju vjerodostojne isprave',
            'DU-2026-005' => 'Isplata iz trgovačkih ugovora',
            'DU-2026-010' => 'Sporovi u vezi s gradnjom i prostornim uređenjem',
        ];
        foreach ($categoryNames as $number => $name) {
            $category = DisputeCategory::query()->where('kind', $created[$number]->kind)->where('name', $name)->first();
            if ($category !== null) {
                $created[$number]->update(['dispute_category_id' => $category->id]);
            }
        }

        $this->enrich($org, $users, $parties, $created);

        return $created;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Party>  $parties
     * @param  array<string, Matter>  $matters
     */
    private function enrich(Organization $org, array $users, array $parties, array $matters): void
    {
        $hearing = $this->event($org, $matters['DU-2026-001'], $users['ivana'], CourtEventType::Hearing, 'Ročište o naknadi štete', now()->addDays(5)->setTime(9, 30), 'Općinski građanski sud u Zagrebu');
        $this->suppressDueReminders($hearing);
        $this->time($org, $matters['DU-2026-001'], $users['ivana'], 'Priprema svjedoka i pregled medicinske dokumentacije', 90, 12000, TimeEntryStatus::Draft, now()->subDays(2));
        $this->time($org, $matters['DU-2026-001'], $users['tin'], 'Sastavljanje kronologije nesreće', 45, 8000, TimeEntryStatus::Approved, now()->subDays(4));
        $this->expense($org, $matters['DU-2026-001'], ExpenseCategory::CourtFee, 'Sudska pristojba na tužbu', 6600);
        $invoice = $this->invoice($org, $matters['DU-2026-001'], $parties['marina'], 'RN-2026-001', 24000, 0, now()->subDays(6), now()->addDays(8), 'Satnica, priprema tužbe');
        $this->time($org, $matters['DU-2026-001'], $users['ivana'], 'Sastavljanje tužbe', 120, 12000, TimeEntryStatus::Approved, now()->subDays(6), $invoice->id);

        $this->invoice($org, $matters['DU-2026-002'], $parties['klara'], 'RN-2026-002', 80000, 80000, now()->subDays(40), now()->subDays(20), 'Paušal za raskid ugovora', InvoiceStatus::Paid);
        $this->event($org, $matters['DU-2026-002'], $users['ivana'], CourtEventType::Meeting, 'Sastanak s klijenticom', now()->subDays(18)->setTime(11, 0), null, now()->subDays(18));

        $this->event($org, $matters['DU-2025-014'], $users['ivana'], CourtEventType::Hearing, 'Ročište, odgođeno', now()->subDays(30)->setTime(10, 0), 'Općinski građanski sud u Zagrebu', now()->subDays(30));

        $criminalHearing = $this->event($org, $matters['DU-2026-003'], $users['luka'], CourtEventType::Hearing, 'Rasprava', now()->addDay()->setTime(13, 0), 'Općinski kazneni sud u Zagrebu');
        $this->suppressDueReminders($criminalHearing);
        EthicalWall::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-003']->id,
            'user_id' => $users['tin']->id,
            'reason' => 'Vježbenik ne smije vidjeti obranu jer je u istom predmetu ranije radio zapisnik na sudu.',
            'created_by_user_id' => $users['petra']->id,
        ]);
        TimelineEntry::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-003']->id,
            'type' => TimelineEntryType::HearingNote,
            'body' => 'Pripremni razgovor: klijent ostaje pri obrani da nije bio na mjestu događaja.',
            'occurred_at' => now()->subDays(3),
            'user_id' => $users['luka']->id,
            'visible_to_client' => false,
        ]);

        $appeal = $this->event($org, $matters['DU-2026-004'], $users['luka'], CourtEventType::AppealDeadline, 'Rok za žalbu na presudu', now()->addDays(3)->setTime(23, 59), 'Županijski sud u Zagrebu', null, true);
        $this->suppressDueReminders($appeal);

        $this->spnft($org, $matters['DU-2026-005'], $users['sara']);
        $this->tariffCharge($org, $matters['DU-2026-005'], $users['sara']);
        $overdue = $this->invoice($org, $matters['DU-2026-005'], $parties['hotel'], 'RN-2026-014', 50000, 0, now()->subDays(40), now()->subDays(20), 'Nagrada po tarifi, tužba', InvoiceStatus::Overdue, EInvoiceStatus::Failed);
        foreach ([1, 7, 14] as $day) {
            InvoiceReminder::query()->create([
                'invoice_id' => $overdue->id,
                'days_after_due' => $day,
                'sent_at' => now()->subDays(14 - $day),
            ]);
        }
        TrustMovement::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-005']->id,
            'direction' => TrustDirection::In,
            'amount_cents' => 500000,
            'occurred_on' => now()->subDays(25)->toDateString(),
            'counterparty' => 'Hotel Jadran d.o.o.',
            'purpose' => 'Predujam za sudsku pristojbu i vještačenje',
            'created_by_user_id' => $users['maja']->id,
        ]);
        TrustMovement::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-005']->id,
            'direction' => TrustDirection::Out,
            'amount_cents' => 120000,
            'occurred_on' => now()->subDays(20)->toDateString(),
            'counterparty' => 'Trgovački sud u Zagrebu',
            'purpose' => 'Sudska pristojba',
            'created_by_user_id' => $users['maja']->id,
        ]);
        LimitationEstimate::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-005']->id,
            'basis' => 'goods_3',
            'starts_on' => '2024-06-01',
            'suggested_on' => '2027-06-01',
            'confirmed_by_user_id' => null,
        ]);

        ConflictCheck::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-006']->id,
            'result' => ConflictResult::Hard,
            'matches' => [[
                'party_id' => $parties['hotel']->id,
                'name' => $parties['hotel']->name,
                'oib' => $parties['hotel']->oib,
                'side' => PartySide::Opposing->value,
                'level' => 'hard',
                'reason' => 'Isti OIB je na predmetu DU-2026-005 kao naš klijent.',
                'matter_number' => 'DU-2026-005',
            ]],
            'checked_by_user_id' => $users['sara']->id,
            'acknowledged_by_user_id' => $users['petra']->id,
            'acknowledged_at' => now()->subDays(9),
            'note' => 'Hotel Jadran je klijent u naplati, a ovdje je osiguranik protivne strane. Predmet vodi Sara, Petra je potvrdila da nema razmjene podataka.',
        ]);

        $partial = $this->invoice($org, $matters['DU-2025-019'], $parties['banka'], 'RN-2025-088', 150000, 60000, now()->subDays(15), now()->addDays(15), 'Paušal za prijavu tražbine u stečaju', InvoiceStatus::Partial, EInvoiceStatus::Sent);
        Payment::query()->create([
            'organization_id' => $org->id,
            'invoice_id' => $partial->id,
            'amount_cents' => 60000,
            'paid_on' => now()->subDays(4)->toDateString(),
            'note' => 'Djelomična uplata',
        ]);

        $objection = $this->event($org, $matters['DU-2026-007'], $users['ivana'], CourtEventType::ObjectionDeadline, 'Rok za tužbu zbog otkaza', now()->addDays(10)->setTime(23, 59), 'Općinski radni sud u Zagrebu', null, true);
        $this->suppressDueReminders($objection);
        $this->time($org, $matters['DU-2026-007'], $users['ivana'], 'Analiza odluke o otkazu', 75, 12000, TimeEntryStatus::Approved, now()->subDays(1));
        $this->time($org, $matters['DU-2026-007'], $users['tin'], 'Pregled evidencije radnog vremena', 30, 8000, TimeEntryStatus::Draft, now()->subDay());

        $this->time($org, $matters['DU-2025-011'], $users['ivana'], 'Sastanak koji se ne naplaćuje jer je predmet pauziran', 60, 12000, TimeEntryStatus::WrittenOff, now()->subDays(40));

        $limitationEvent = $this->event($org, $matters['DU-2026-008'], $users['luka'], CourtEventType::Limitation, 'Zastara ovršne tražbine', now()->addYears(6)->setTime(12, 0), null, null, true);
        LimitationEstimate::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-008']->id,
            'basis' => 'judgment_10',
            'starts_on' => now()->subYears(4)->toDateString(),
            'suggested_on' => now()->addYears(6)->toDateString(),
            'court_event_id' => $limitationEvent->id,
            'confirmed_by_user_id' => $users['luka']->id,
        ]);

        $this->event($org, $matters['DU-2026-009'], $users['luka'], CourtEventType::Hearing, 'Ročište povodom prigovora', now()->addDays(12)->setTime(8, 45), 'Općinski sud u Zagrebu');

        $this->invoice($org, $matters['DU-2024-030'], $parties['banka'], 'RN-2024-210', 40000, 40000, now()->subMonths(8), now()->subMonths(7), 'Paušal, namirena ovrha', InvoiceStatus::Paid);

        $this->event($org, $matters['DU-2026-010'], $users['sara'], CourtEventType::Inspection, 'Očevid na gradilištu', now()->addDays(8)->setTime(10, 0), 'Upravni sud u Zagrebu');

        $this->invoice($org, $matters['DU-2026-011'], $parties['medo'], 'RN-2026-021', 36000, 0, now()->subDays(3), now()->addDays(11), 'Tarifna nagrada, žalba', InvoiceStatus::Unpaid, EInvoiceStatus::Prepared);

        EthicalWall::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-012']->id,
            'user_id' => $users['sara']->id,
            'reason' => 'Sara je prije dolaska u ured savjetovala Tomislava Horvata o istom braku. Ne smije otvoriti spis.',
            'created_by_user_id' => $users['petra']->id,
        ]);

        $this->time($org, $matters['DU-2026-013'], $users['sara'], 'Interni pregled data room-a, nije za naplatu', 120, 15000, TimeEntryStatus::NonBillable, now()->subDays(2));
        ConflictCheck::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-013']->id,
            'result' => ConflictResult::Clear,
            'matches' => [],
            'checked_by_user_id' => $users['sara']->id,
            'note' => 'Ni kupac ni ciljna društva nisu u drugim predmetima.',
        ]);

        ConflictCheck::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matters['DU-2026-014']->id,
            'result' => ConflictResult::Potential,
            'matches' => [[
                'party_id' => $parties['nikola_b']->id,
                'name' => 'Nikola Radić',
                'oib' => $parties['nikola_b']->oib,
                'side' => PartySide::Opposing->value,
                'level' => 'potential',
                'reason' => 'Ime se podudara s klijentom Nikole Radića (drugi OIB) na predmetu koji nije otvoren. OIB-ovi se razlikuju.',
                'matter_number' => null,
            ]],
            'checked_by_user_id' => $users['ivana']->id,
            'acknowledged_by_user_id' => $users['petra']->id,
            'acknowledged_at' => now()->subDays(2),
            'note' => 'Dva različita OIB-a. Petra je potvrdila da se ne radi o istoj osobi.',
        ]);
        $this->linkParty($org, $matters['DU-2026-014'], $parties['nikola_b'], MatterPartyRole::ThirdParty, PartySide::Other);

        $this->expense($org, $matters['DU-2026-015'], ExpenseCategory::Travel, 'Put na pregovore u Split', 8400);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function matterSpecs(): array
    {
        return [
            $this->spec('DU-2026-001', 'Naknada štete u prometnoj nezgodi', MatterKind::Civil, MatterStatus::Active, BillingMethod::Hourly, 'ivana', ['ivana', 'tin'], 'marina', MatterPartyRole::Plaintiff, 'ivan', MatterPartyRole::Defendant, 'Općinski građanski sud u Zagrebu', 'P', '312', 2026, 850000, 12000, null, null, 'Tužba podnesena, čeka se ročište. Klijentica ima pristup portalu.', true),
            $this->spec('DU-2026-002', 'Raskid ugovora o djelu', MatterKind::Civil, MatterStatus::Active, BillingMethod::Flat, 'ivana', ['ivana'], 'klara', MatterPartyRole::Plaintiff, 'agencija', MatterPartyRole::Defendant, 'Općinski građanski sud u Zagrebu', 'P', '88', 2026, 420000, null, 80000, null, 'Nagodba je plaćena, čeka se povlačenje tužbe.'),
            $this->spec('DU-2025-014', 'Smetanje posjeda', MatterKind::Civil, MatterStatus::Paused, BillingMethod::Hourly, 'ivana', ['ivana'], 'fran', MatterPartyRole::Plaintiff, 'davor', MatterPartyRole::Defendant, 'Općinski građanski sud u Zagrebu', 'P', '1402', 2025, 150000, 12000, null, null, 'Predmet pauziran dok stranke pregovaraju o ogradi.'),
            $this->spec('DU-2024-008', 'Ostavinski postupak', MatterKind::Civil, MatterStatus::Archived, BillingMethod::Flat, 'ivana', ['ivana'], 'kolar', MatterPartyRole::Client, null, null, 'Općinski građanski sud u Zagrebu', 'O', '77', 2024, null, null, 60000, null, 'Rješenje o nasljeđivanju je pravomoćno. Spis je arhiviran.'),
            $this->spec('DU-2026-003', 'Obrana u kaznenom postupku', MatterKind::Criminal, MatterStatus::Active, BillingMethod::SuccessFee, 'luka', ['luka', 'tin'], 'marko', MatterPartyRole::Client, 'rh', MatterPartyRole::OpposingCounsel, 'Općinski kazneni sud u Zagrebu', 'K', '19', 2026, null, null, null, 'Nagrada samo ako postupak završi oslobađajućom presudom.', 'Rasprava je sutra. Vježbenik je dodijeljen, ali je iza etičkog zida i predmet ne vidi.'),
            $this->spec('DU-2026-004', 'Imovinskopravni zahtjev oštećenice', MatterKind::Criminal, MatterStatus::Active, BillingMethod::Hourly, 'luka', ['luka'], 'ema', MatterPartyRole::Client, 'rh', MatterPartyRole::OpposingCounsel, 'Županijski sud u Zagrebu', 'Kž', '441', 2026, 300000, 15000, null, null, 'Žalbeni rok ističe za tri dana i prekluzivan je.'),
            $this->spec('DU-2024-021', 'Okončana kaznena obrana', MatterKind::Criminal, MatterStatus::Archived, BillingMethod::Flat, 'luka', ['luka'], 'marko', MatterPartyRole::Client, 'rh', MatterPartyRole::OpposingCounsel, 'Općinski kazneni sud u Zagrebu', 'K', '4', 2024, null, null, 200000, null, 'Oslobađajuća presuda je pravomoćna.'),
            $this->spec('DU-2026-005', 'Naplata tražbine za hotelske usluge', MatterKind::Commercial, MatterStatus::Active, BillingMethod::Tariff, 'sara', ['sara'], 'hotel', MatterPartyRole::Plaintiff, 'sunce', MatterPartyRole::Defendant, 'Trgovački sud u Zagrebu', 'P', '55', 2026, 2500000, null, null, null, 'SPNFT lista je djelomično ispunjena. Na fiducijarnom računu stoji ostatak predujma.', false, true),
            $this->spec('DU-2026-006', 'Regres osiguranja protiv hotela', MatterKind::Commercial, MatterStatus::Active, BillingMethod::Hourly, 'sara', ['sara'], 'val', MatterPartyRole::Plaintiff, 'hotel', MatterPartyRole::Defendant, 'Trgovački sud u Zagrebu', 'P', '61', 2026, 1800000, 15000, null, null, 'Tvrdi sukob: Hotel Jadran je klijent u drugom predmetu. Partnerica je sukob potvrdila.'),
            $this->spec('DU-2025-019', 'Prijava tražbine u stečaju', MatterKind::Commercial, MatterStatus::Active, BillingMethod::Flat, 'sara', ['sara'], 'banka', MatterPartyRole::Creditor, 'stekstil', MatterPartyRole::Debtor, 'Trgovački sud u Zagrebu', 'St', '12', 2025, 6400000, null, 150000, null, 'Tražbina je prijavljena. Račun je djelomično plaćen, e-račun je poslan.'),
            $this->spec('DU-2026-007', 'Nedopušteni otkaz', MatterKind::Labor, MatterStatus::Active, BillingMethod::Hourly, 'ivana', ['ivana', 'tin'], 'ana', MatterPartyRole::Plaintiff, 'keramika', MatterPartyRole::Defendant, 'Općinski radni sud u Zagrebu', 'Pr', '18', 2026, 960000, 12000, null, null, 'Rok za tužbu teče. Dio sati čeka odobrenje partnerice.'),
            $this->spec('DU-2025-011', 'Uznemiravanje na radu', MatterKind::Labor, MatterStatus::Paused, BillingMethod::Hourly, 'ivana', ['ivana'], 'mobbing', MatterPartyRole::Plaintiff, 'agencija', MatterPartyRole::Defendant, 'Općinski radni sud u Zagrebu', 'Pr', '203', 2025, 400000, 12000, null, null, 'Klijentica je zatražila stanku. Sati su otpisani.'),
            $this->spec('DU-2026-008', 'Ovrha na temelju pravomoćne presude', MatterKind::Enforcement, MatterStatus::Active, BillingMethod::Tariff, 'luka', ['luka'], 'banka', MatterPartyRole::Creditor, 'josip', MatterPartyRole::Debtor, 'Općinski sud u Zagrebu', 'Ovr', '900', 2026, 2100000, null, null, null, 'Zastara je potvrđena i upisana u kalendar.'),
            $this->spec('DU-2026-009', 'Ovrha na temelju vjerodostojne isprave', MatterKind::Enforcement, MatterStatus::Active, BillingMethod::Flat, 'luka', ['luka'], 'sunce', MatterPartyRole::Creditor, 'josip', MatterPartyRole::Debtor, 'Općinski sud u Zagrebu', 'Ovr', '144', 2026, 180000, null, 45000, null, 'Ovršenik je uložio prigovor. Ročište je za dva tjedna.'),
            $this->spec('DU-2024-030', 'Namirena ovrha', MatterKind::Enforcement, MatterStatus::Archived, BillingMethod::Flat, 'luka', ['luka'], 'banka', MatterPartyRole::Creditor, 'ivan', MatterPartyRole::Debtor, 'Općinski sud u Zagrebu', 'Ovr', '11', 2024, 90000, null, 40000, null, 'Tražbina je namirena, račun je plaćen, spis je u arhivi.'),
            $this->spec('DU-2026-010', 'Poništaj građevinske dozvole', MatterKind::Administrative, MatterStatus::Active, BillingMethod::Hourly, 'sara', ['sara'], 'delta', MatterPartyRole::Plaintiff, 'grad', MatterPartyRole::Defendant, 'Upravni sud u Zagrebu', 'Us', '33', 2026, null, 15000, null, null, 'Zakazan je očevid na gradilištu.'),
            $this->spec('DU-2026-011', 'Porezni spor', MatterKind::Administrative, MatterStatus::Active, BillingMethod::Tariff, 'sara', ['sara'], 'medo', MatterPartyRole::Plaintiff, 'porezna', MatterPartyRole::Defendant, 'Upravni sud u Osijeku', 'Us', '8', 2026, 720000, null, null, null, 'Žalba je podnesena. E-račun je pripremljen, još nije poslan.'),
            $this->spec('DU-2026-012', 'Razvod braka i skrbništvo', MatterKind::Civil, MatterStatus::Active, BillingMethod::Hourly, 'ivana', ['ivana', 'sara'], 'elena', MatterPartyRole::Plaintiff, 'tomislav', MatterPartyRole::Defendant, 'Općinski građanski sud u Zagrebu', 'P', '2', 2026, null, 12000, null, null, 'Osjetljiv spis. Sara je na predmetu, ali je iza etičkog zida i ne vidi ga.'),
            $this->spec('DU-2026-013', 'Due diligence kupnje udjela', MatterKind::Commercial, MatterStatus::Active, BillingMethod::Flat, 'sara', ['sara', 'petra'], 'holding', MatterPartyRole::Client, 'invest', MatterPartyRole::ThirdParty, '—', null, null, 2026, 15000000, null, 250000, null, 'Provjera sukoba je čista. Dio rada je označen kao nenaplativ.'),
            $this->spec('DU-2026-014', 'Regres nakon prometne nezgode', MatterKind::Civil, MatterStatus::Active, BillingMethod::Hourly, 'ivana', ['ivana'], 'nikola_a', MatterPartyRole::Plaintiff, 'val', MatterPartyRole::Defendant, 'Općinski građanski sud u Puli', 'P', '70', 2026, 540000, 12000, null, null, 'Mogući sukob imena: postoji drugi Nikola Radić s drugim OIB-om.'),
            $this->spec('DU-2025-006', 'Prekršajni postupak u mirovanju', MatterKind::Criminal, MatterStatus::Paused, BillingMethod::Flat, 'luka', ['luka'], 'iva', MatterPartyRole::Client, 'rh', MatterPartyRole::OpposingCounsel, 'Prekršajni sud u Sisku', 'Pp', '300', 2025, null, null, 30000, null, 'Sud je odgodio ročište na neodređeno. Predmet je pauziran.'),
            $this->spec('DU-2026-015', 'Kolektivni radni spor', MatterKind::Labor, MatterStatus::Active, BillingMethod::SuccessFee, 'petra', ['petra', 'ivana'], 'sindikat', MatterPartyRole::Plaintiff, 'market', MatterPartyRole::Defendant, 'Županijski sud u Zagrebu', 'Pr', '1', 2026, 5000000, null, null, 'Osnovna nagrada plus postotak ako sindikat uspije u sporu.', 'Partnerica vodi predmet uz Ivanu. Putni trošak još nije fakturiran.'),
        ];
    }

    /**
     * @param  list<string>  $assignees
     * @return array<string, mixed>
     */
    private function spec(
        string $number,
        string $title,
        MatterKind $kind,
        MatterStatus $status,
        BillingMethod $billing,
        string $lead,
        array $assignees,
        string $client,
        MatterPartyRole $clientRole,
        ?string $opposing,
        ?MatterPartyRole $opposingRole,
        string $court,
        ?string $mark,
        ?string $case,
        int $year,
        ?int $value,
        ?int $rate,
        ?int $flat,
        ?string $success,
        string $note,
        bool $clientVisible = false,
        bool $spnft = false,
    ): array {
        return [
            'number' => $number,
            'title' => $title,
            'kind' => $kind,
            'status' => $status,
            'billing' => $billing,
            'assignees' => $assignees,
            'client' => $client,
            'client_role' => $clientRole,
            'opposing' => $opposing,
            'opposing_role' => $opposingRole,
            'court' => $court === '—' ? null : $court,
            'mark' => $mark,
            'case' => $case,
            'year' => $year,
            'value' => $value,
            'rate' => $rate,
            'flat' => $flat,
            'success' => $success,
            'note' => $note,
            'client_visible' => $clientVisible,
            'spnft' => $spnft,
            'lead' => $lead,
        ];
    }

    private function linkParty(Organization $org, Matter $matter, Party $party, MatterPartyRole $role, PartySide $side): void
    {
        MatterParty::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'role' => $role,
            'side' => $side,
        ]);
    }

    private function event(
        Organization $org,
        Matter $matter,
        User $responsible,
        CourtEventType $type,
        string $title,
        \Carbon\Carbon $starts,
        ?string $court,
        ?\Carbon\Carbon $completed = null,
        bool $preclusive = false,
    ): CourtEvent {
        return CourtEvent::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'type' => $type,
            'title' => $title,
            'court_name' => $court,
            'starts_at' => $starts,
            'ends_at' => $type === CourtEventType::Hearing ? $starts->copy()->addHours(2) : null,
            'responsible_user_id' => $responsible->id,
            'is_preclusive' => $preclusive,
            'notes' => null,
            'completed_at' => $completed,
            'source' => 'office',
        ]);
    }

    private function suppressDueReminders(CourtEvent $event): void
    {
        foreach ([10080, 1440, 60] as $offset) {
            if (now()->lt($event->starts_at->copy()->subMinutes($offset))) {
                continue;
            }
            DeadlineReminder::query()->create([
                'court_event_id' => $event->id,
                'offset_minutes' => $offset,
                'email_sent_at' => now(),
                'in_app_sent_at' => now(),
            ]);
        }
    }

    private function time(
        Organization $org,
        Matter $matter,
        User $user,
        string $description,
        int $minutes,
        int $rate,
        TimeEntryStatus $status,
        \Carbon\Carbon $started,
        ?int $invoiceId = null,
    ): void {
        TimeEntry::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'user_id' => $user->id,
            'description' => $description,
            'started_at' => $started,
            'ended_at' => $started->copy()->addMinutes($minutes),
            'minutes' => $minutes,
            'hourly_rate_cents' => $rate,
            'status' => $status,
            'invoice_id' => $invoiceId,
        ]);
    }

    private function expense(Organization $org, Matter $matter, ExpenseCategory $category, string $description, int $cents): void
    {
        Expense::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'category' => $category,
            'description' => $description,
            'bill_to' => 'client',
            'amount_cents' => $cents,
        ]);
    }

    private function invoice(
        Organization $org,
        Matter $matter,
        Party $buyer,
        string $number,
        int $subtotal,
        int $paid,
        \Carbon\Carbon $issued,
        \Carbon\Carbon $due,
        string $line,
        ?InvoiceStatus $status = null,
        EInvoiceStatus $eInvoice = EInvoiceStatus::None,
    ): Invoice {
        $vat = (int) round($subtotal * 0.25);
        $total = $subtotal + $vat;
        if ($status === null) {
            $status = $paid >= $total ? InvoiceStatus::Paid : ($paid > 0 ? InvoiceStatus::Partial : InvoiceStatus::Unpaid);
        }

        $invoice = Invoice::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'party_id' => $buyer->id,
            'number' => $number,
            'issue_date' => $issued->toDateString(),
            'due_date' => $due->toDateString(),
            'status' => $status,
            'subtotal_cents' => $subtotal,
            'vat_cents' => $vat,
            'total_cents' => $total,
            'paid_cents' => $paid,
            'vat_rate' => 25,
            'buyer_name' => $buyer->name,
            'buyer_oib' => $buyer->oib,
            'buyer_address' => trim(($buyer->address ?? '').', '.($buyer->city ?? '')),
            'e_invoice_status' => $eInvoice,
            'e_invoice_error' => $eInvoice === EInvoiceStatus::Failed ? 'Demo: primatelj nije pronađen u Moj-eRačunu.' : null,
            'e_invoice_sent_at' => $eInvoice === EInvoiceStatus::Sent ? now()->subDays(5) : null,
        ]);

        InvoiceLine::query()->create([
            'invoice_id' => $invoice->id,
            'description' => $line,
            'quantity' => 1,
            'unit_price_cents' => $subtotal,
            'line_total_cents' => $subtotal,
            'vat_rate' => 25,
        ]);

        if ($paid > 0 && $status === InvoiceStatus::Paid) {
            Payment::query()->create([
                'organization_id' => $org->id,
                'invoice_id' => $invoice->id,
                'amount_cents' => $paid,
                'paid_on' => $due->toDateString(),
                'note' => 'Uplata u cijelosti',
            ]);
        }

        return $invoice;
    }

    private function spnft(Organization $org, Matter $matter, User $user): void
    {
        foreach (['identity', 'beneficial_owner', 'purpose', 'pep'] as $item) {
            SpnftCheck::query()->create([
                'organization_id' => $org->id,
                'matter_id' => $matter->id,
                'item' => $item,
                'completed_by_user_id' => $user->id,
                'completed_at' => now()->subDays(8),
            ]);
        }
    }

    private function tariffCharge(Organization $org, Matter $matter, User $user): void
    {
        $action = TariffAction::query()->where('code', 'tuzba')->first();
        if ($action === null) {
            return;
        }
        $quote = app(TariffCatalog::class)->quote($action, $matter->dispute_value_cents);
        TariffCharge::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'tariff_version_id' => $action->tariff_version_id,
            'tariff_action_id' => $action->id,
            'audience' => FeeAudience::Client,
            'description' => $action->label,
            'points' => $quote['points'],
            'amount_cents' => $quote['amount_cents'],
            'created_by_user_id' => $user->id,
        ]);
    }

    private function portalClient(Organization $org, Party $marina): void
    {
        ClientAccount::query()->create([
            'organization_id' => $org->id,
            'party_id' => $marina->id,
            'name' => $marina->name,
            'email' => 'marina.bozic@demo-ured.test',
            'password' => self::PASSWORD,
        ]);
    }

    private function sharedDocument(Organization $org, Matter $matter, User $uploader): void
    {
        $path = 'demo-ured/'.$org->id.'/tuzba-naknada-stete.txt';
        $body = "DEMO DOKUMENT\nTužba za naknadu štete, predmet DU-2026-001.\nOvaj tekst služi samo za isprobavanje pregleda i portala klijenta.\n";
        Storage::disk('local')->put($path, $body);
        MatterDocument::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'folder' => 'Podnesci',
            'original_name' => 'Tuzba-naknada-stete.txt',
            'path' => $path,
            'size_bytes' => strlen($body),
            'mime' => 'text/plain',
            'version' => 1,
            'uploaded_by_user_id' => $uploader->id,
            'shared_with_client' => true,
        ]);
    }

    private function notification(Organization $org, User $petra, Matter $matter): void
    {
        OfficeNotification::query()->create([
            'organization_id' => $org->id,
            'user_id' => $petra->id,
            'title' => 'Rasprava sutra: '.$matter->internal_number,
            'body' => 'Luka Babić ima raspravu u kaznenom predmetu. Predmet je iza etičkog zida za vježbenika.',
        ]);
    }

    private function oib(string $base10): string
    {
        $a = 10;
        for ($i = 0; $i < 10; $i++) {
            $a = ($a + (int) $base10[$i]) % 10;
            if ($a === 0) {
                $a = 10;
            }
            $a = ($a * 2) % 11;
        }
        $check = 11 - $a;
        if ($check === 10) {
            $check = 0;
        }
        $oib = $base10.$check;
        if (! CroatianOib::isValid($oib)) {
            throw new \RuntimeException('Neispravan demo OIB: '.$oib);
        }

        return $oib;
    }
}
