<?php

namespace Tests\Feature\Auth;

use App\Models\LawyerDirectoryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LawyerDirectoryRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'identity.core_auth_enabled' => false,
            'admin_console.webhook_url' => null,
            'services.sudreg.base_url' => 'https://sudreg.test/api',
            'services.sudreg.client_id' => 'klijent',
            'services.sudreg.client_secret' => 'tajna',
        ]);
    }

    public function test_sync_imports_offices_and_skips_trainees(): void
    {
        $this->artisan('legal:sync-lawyer-directory', ['--path' => base_path('tests/Fixtures/hok-directory.csv')])
            ->assertSuccessful()
            ->expectsOutputToContain('Učitano ureda: 3');

        $this->assertDatabaseMissing('lawyer_directory_entries', ['name' => 'Marko Vježbenik']);
        $this->assertDatabaseMissing('lawyer_directory_entries', ['name' => 'Iva Pauza']);
        $this->assertDatabaseHas('lawyer_directory_entries', [
            'name' => 'Ana Anić',
            'office_kind' => 'sole',
            'address' => 'Ilica 2',
            'city' => '10000 Zagreb',
            'phone' => '01/222',
        ]);
        $this->assertDatabaseHas('lawyer_directory_entries', [
            'name' => 'ANIĆ odvjetničko društvo d.o.o.',
            'office_kind' => 'firm',
        ]);
        $this->assertDatabaseHas('lawyer_directory_entries', [
            'name' => 'Ured Partneri',
            'office_kind' => 'joint',
            'city' => 'Split',
        ]);
    }

    public function test_workbook_keeps_an_employed_lawyer_as_a_sole_office(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hok').'.xlsx';
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([
            ['Imenik'],
            ['Ime / Naziv', 'Status', 'Zaposlen/a kod', 'Adresa', 'Mjesto', 'Telefon'],
            ['Eva Odvjetnica', 'Odvjetnica', 'Neko društvo', "Ulica 1\n21000 Split", 'Split', ''],
            ['Vježbenica', 'Odvjetnička vježbenica', '', 'Ulica 2', 'Split', '021'],
        ]);
        (new Xlsx($sheet))->save($path);

        $this->artisan('legal:sync-lawyer-directory', ['--path' => $path])->assertSuccessful();

        $this->assertDatabaseHas('lawyer_directory_entries', [
            'name' => 'Eva Odvjetnica',
            'office_kind' => 'sole',
            'address' => 'Ulica 1',
            'city' => '21000 Split',
            'phone' => null,
        ]);
        $this->assertDatabaseMissing('lawyer_directory_entries', ['name' => 'Vježbenica']);
        unlink($path);
    }

    public function test_empty_directory_does_not_replace_a_previous_sync(): void
    {
        $this->artisan('legal:sync-lawyer-directory', ['--path' => base_path('tests/Fixtures/hok-directory.csv')])
            ->assertSuccessful();

        $empty = tempnam(sys_get_temp_dir(), 'hok');
        file_put_contents($empty, "Ime / Naziv,Status,Adresa,Mjesto,Telefon\nMarko,Odvjetnički vježbenik,Ilica 3,Zagreb,01\n");

        $this->artisan('legal:sync-lawyer-directory', ['--path' => $empty])
            ->assertFailed()
            ->expectsOutputToContain('nijedan ured');

        $this->assertSame(3, LawyerDirectoryEntry::query()->count());
        unlink($empty);
    }

    public function test_directory_search_returns_offices_and_not_trainees(): void
    {
        $this->artisan('legal:sync-lawyer-directory', ['--path' => base_path('tests/Fixtures/hok-directory.csv')]);

        $this->get(route('register.organization'))
            ->assertOk()
            ->assertSee('Počnite pisati naziv ili grad')
            ->assertSee('Upiši ručno')
            ->assertDontSee('Pronađi ured')
            ->assertSee('Poslovni IBAN');

        $response = $this->getJson(route('register.organization.directory', ['q' => 'Anić']));

        $response->assertOk();
        $results = collect($response->json('results'));
        $this->assertEqualsCanonicalizing([
            'ANIĆ odvjetničko društvo d.o.o.',
            'Ana Anić',
        ], $results->pluck('name')->all());
        $this->assertTrue($results->pluck('office_kind_label')->contains('Odvjetničko društvo'));
        $this->assertTrue($results->contains(fn (array $entry) => $entry['name'] === 'Ana Anić' && $entry['city'] === '10000 Zagreb'));
    }

    public function test_court_lookup_fills_a_firm_and_skips_a_sole_office(): void
    {
        Http::fake([
            'https://sudreg.test/api/oauth/token' => Http::response(['access_token' => 'tok']),
            'https://sudreg.test/api/javni/subjekti*' => Http::response([
                [
                    'mbs' => '080111111',
                    'oib' => '12345678903',
                    'status' => 1,
                    'tvrtke' => [['naziv' => 'ANIĆ odvjetničko društvo d.o.o.']],
                    'sjedista' => [[
                        'ulica' => 'Ilica',
                        'kucni_broj' => '1',
                        'naziv_naselja' => 'Zagreb',
                        'postanski_broj' => '10000',
                    ]],
                ],
                [
                    'mbs' => '080222222',
                    'oib' => '10987654326',
                    'status' => 'brisan',
                    'tvrtke' => [['naziv' => 'ANIĆ odvjetničko društvo d.o.o.']],
                    'sjedista' => [['ulica' => 'Stara', 'kucni_broj' => '2', 'naziv_naselja' => 'Rijeka']],
                ],
                [
                    'mbs' => '080333333',
                    'oib' => '11111111119',
                    'status' => 1,
                    'tvrtke' => [['naziv' => 'Druga tvrtka d.o.o.']],
                    'sjedista' => [['ulica' => 'Gajeva', 'kucni_broj' => '4', 'naziv_naselja' => 'Split']],
                ],
            ]),
        ]);

        $this->getJson(route('register.organization.court', [
            'name' => 'ANIĆ odvjetničko društvo d.o.o.',
            'office_kind' => 'firm',
        ]))->assertOk()->assertJson([
            'matches' => [[
                'name' => 'ANIĆ odvjetničko društvo d.o.o.',
                'oib' => '12345678903',
                'mbs' => '080111111',
                'address' => 'Ilica 1',
                'city' => '10000 Zagreb',
            ]],
        ]);

        Http::preventStrayRequests();
        $this->getJson(route('register.organization.court', [
            'name' => 'Ana Anić',
            'office_kind' => 'sole',
        ]))->assertOk()->assertJson(['matches' => []]);
    }

    public function test_firm_registration_stores_the_court_register_mbs(): void
    {
        Http::fake([
            'https://sudreg.test/api/oauth/token' => Http::response(['access_token' => 'tok']),
            'https://sudreg.test/api/javni/detalji_subjekta*' => Http::response([
                'mbs' => '080111111',
                'oib' => '12345678903',
                'tvrtke' => [['naziv' => 'Nova tvrtka društvo s ograničenom odgovornošću']],
                'sjedista' => [['ulica' => 'Ilica', 'kucni_broj' => '1', 'naziv_naselja' => 'Zagreb']],
            ]),
        ]);

        $this->post(route('register.organization'), $this->firm([
            'mbs' => '000000000',
        ]))->assertRedirect(route('registration.pending'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Nova tvrtka odvjetničko društvo d.o.o.',
            'office_kind' => 'firm',
            'oib' => '12345678903',
            'mbs' => '080111111',
            'status' => 'pending',
        ]);
    }

    public function test_firm_registration_stops_when_the_court_name_differs(): void
    {
        Http::fake([
            'https://sudreg.test/api/oauth/token' => Http::response(['access_token' => 'tok']),
            'https://sudreg.test/api/javni/detalji_subjekta*' => Http::response([
                'mbs' => '080111111',
                'oib' => '12345678903',
                'tvrtke' => [['naziv' => 'Posve druga tvrtka d.o.o.']],
                'sjedista' => [['ulica' => 'Ilica', 'kucni_broj' => '1', 'naziv_naselja' => 'Zagreb']],
            ]),
        ]);

        $this->post(route('register.organization'), $this->firm())
            ->assertSessionHasErrors('oib');

        $this->assertDatabaseCount('organizations', 0);
    }

    public function test_joint_office_is_not_checked_in_the_court_register(): void
    {
        Http::preventStrayRequests();

        $this->post(route('register.organization'), $this->firm([
            'office_kind' => 'joint',
            'name' => 'Ured Partneri',
        ]))->assertRedirect(route('registration.pending'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Ured Partneri',
            'office_kind' => 'joint',
            'mbs' => null,
        ]);
    }

    public function test_signed_in_user_can_open_another_office(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('register.organization'), [
            'office_kind' => 'sole',
            'name' => 'Drugi ured',
            'oib' => '10987654326',
            'address' => 'Gajeva 1',
            'city' => '21000 Split',
            'phone' => '021/333',
            'organization_email' => 'drugi@ured.hr',
            'iban' => 'HR1210010051863000160',
        ])->assertRedirect(route('registration.pending'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('organizations', [
            'name' => 'Drugi ured',
            'oib' => '10987654326',
            'office_kind' => 'sole',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function firm(array $overrides = []): array
    {
        return array_merge([
            'office_kind' => 'firm',
            'name' => 'Nova tvrtka odvjetničko društvo d.o.o.',
            'oib' => '12345678903',
            'address' => 'Ilica 1',
            'city' => '10000 Zagreb',
            'phone' => '01/111',
            'organization_email' => 'office@anic.hr',
            'iban' => 'HR1210010051863000160',
            'admin_name' => 'Ana Anić',
            'admin_email' => 'ana@anic.hr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }
}
