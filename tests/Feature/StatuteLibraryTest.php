<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Matter;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Statute;
use App\Models\StatuteWork;
use App\Models\User;
use App\Services\StatuteImporter;
use App\Services\StatuteWorkGrouper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StatuteLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_keeps_a_law_and_skips_a_regulation(): void
    {
        config(['services.nn.base_url' => 'https://narodne-novine.nn.hr']);
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_ends_with($url, '/api/index')) {
                return Http::response([2024]);
            }
            if (str_contains($url, 'get_index_file.aspx')) {
                return Http::response($this->indexCsv());
            }

            return Http::response('<html><body><p>Tekst zakona o obveznim odnosima (27.3.2024.)</p><script>alert(1)</script></body></html>');
        });

        app(StatuteImporter::class)->pull(10);

        $this->assertSame(2, Statute::query()->count());
        $this->assertSame(1, StatuteWork::query()->count());
        $base = Statute::query()->where('citation', 'NN 34/2024')->first();
        $amendment = Statute::query()->where('citation', 'NN 40/2024')->first();
        $this->assertNotNull($base);
        $this->assertNotNull($amendment);
        $this->assertSame($base->work_id, $amendment->work_id);
        $this->assertSame('Zakon o obveznim odnosima', $base->work->title);
        $this->assertSame('2024-03-27', $base->published_on?->toDateString());
        $this->assertStringContainsString('Tekst zakona o obveznim odnosima', (string) $base->text_plain);
        $this->assertStringNotContainsString('script', (string) $base->text_html);
        $this->assertDatabaseMissing('statutes', ['title' => 'Uredba o cijenama']);

        Http::fake();
        app(StatuteImporter::class)->pull(5);
        Http::assertNothingSent();
    }

    public function test_corrections_and_inflected_titles_share_the_law(): void
    {
        $grouper = app(StatuteWorkGrouper::class);
        $cases = [
            'Ispravak Zakona o grobljima' => 'Zakon o grobljima',
            'Ispravak Odluke o proglašenju Zakona o gnojidbenim proizvodima' => 'Zakon o gnojidbenim proizvodima',
            'Ispravak Zakona o izmjenama i dopunama Zakona o trošarinama' => 'Zakon o trošarinama',
            'Ispravak Zakona o izmjeni i dopunama Zakona o računovodstvu' => 'Zakon o računovodstvu',
            'Zakon o izmjenama i dopunama Kaznenog zakona' => 'Kazneni zakon',
            'Zakon o izmjenama i dopunama Obiteljskog zakona' => 'Obiteljski zakon',
            'Obiteljski zakon' => 'Obiteljski zakon',
            'Zakon o izmjenama i dopunama Općeg poreznog zakona' => 'Opći porezni zakon',
            'Zakon o izmjenama i dopunama Pomorskog zakonika' => 'Pomorski zakonik',
            'Zakon o izmjenama i dopunama Stečajnog zakona' => 'Stečajni zakon',
            'Zakon o izborima zastupnika u Hrvatski sabor (pročišćeni tekst)' => 'Zakon o izborima zastupnika u Hrvatski sabor',
            "Zakon o izmjenama i dopunama Zakona o sudo\u{00AD}vima" => 'Zakon o sudovima',
            'Zaklon o izmjenama Zakona o zaštiti topografija poluvodičkih proizvoda' => 'Zakon o zaštiti topografija poluvodičkih proizvoda',
            'zakona o socijalnopedagoškoj djelatnosti' => 'Zakon o socijalnopedagoškoj djelatnosti',
            'Zakon o Zakladi "Hrvatska za djecu"' => 'Zakon o zakladi »Hrvatska za djecu«',
            'Zakon o zakladi »Hrvatska za djecu«' => 'Zakon o zakladi »Hrvatska za djecu«',
            'Zakon o prestanku važenja Zakona o elektroničkoj ispravi' => 'Zakon o prestanku važenja Zakona o elektroničkoj ispravi',
            'Zakon o provedbi Uredbe (EU) br. 648/2012 Europskog parlamenta i Vijeća od 4. srpnja 2012. godine o OTC izvedenicama' => 'Zakon o provedbi Uredbe (EU) br. 648/2012 o OTC izvedenicama',
            'Zakon o provedbi Uredbe (EU) br. 648/2012 o OTC izvedenicama' => 'Zakon o provedbi Uredbe (EU) br. 648/2012 o OTC izvedenicama',
            'Zakon o provedbi Uredbe (EU) br. 909/2014 o oboljšanju namire' => 'Zakon o provedbi Uredbe (EU) br. 909/2014 o poboljšanju namire',
            'Zakon o provedbi Uredbe (EU) 2019/1148 o stavljanju na tržište i uporabi prekursora eksploziva te izmjeni Uredbe ( EZ) br. 1907/2006' => 'Zakon o provedbi Uredbe (EU) 2019/1148 o stavljanju na tržište i uporabi prekursora eksploziva te izmjeni Uredbe (EZ) br. 1907/2006',
        ];

        foreach ($cases as $publication => $law) {
            $this->assertSame($law, $grouper->workTitle($publication), $publication);
        }

        foreach ([
            'Obiteljski zakon',
            'Zakon o izmjenama i dopunama Obiteljskog zakona',
            'Ispravak Zakona o grobljima',
            'Zakon o grobljima',
        ] as $index => $title) {
            Statute::query()->create([
                'external_id' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2024/1/'.$index,
                'title' => $title,
                'citation' => 'NN 1/2024',
                'document_type' => 'ZAKON',
                'source_url' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2024/1/'.$index.'/hrv/html',
                'fetched_at' => now(),
            ]);
        }

        $grouper->regroup();

        $this->assertSame(2, StatuteWork::query()->count());
        $family = StatuteWork::query()->where('title', 'Obiteljski zakon')->first();
        $graves = StatuteWork::query()->where('title', 'Zakon o grobljima')->first();
        $this->assertNotNull($family);
        $this->assertNotNull($graves);
        $this->assertSame(2, $family->statutes()->count());
        $this->assertSame(2, $graves->statutes()->count());
    }

    public function test_library_search_and_matter_link(): void
    {
        [$user, $organization] = $this->office();
        $matter = Matter::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Naplata',
            'internal_number' => '2026/001',
            'kind' => 'civil',
            'status' => 'active',
            'billing_method' => 'hourly',
            'created_by_user_id' => $user->id,
        ]);
        $matter->assignees()->sync([$user->id]);
        $statute = Statute::query()->create([
            'external_id' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2024/34/10',
            'title' => 'Zakon o obveznim odnosima',
            'citation' => 'NN 34/2024',
            'document_type' => 'ZAKON',
            'published_on' => '2024-03-27',
            'source_url' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2024/34/10/hrv/html',
            'text_html' => '<p>Tekst zakona</p>',
            'text_plain' => 'Tekst zakona o obveznim odnosima',
            'fetched_at' => now(),
        ]);
        app(StatuteWorkGrouper::class)->attachMissing();

        $this->actingAs($user)
            ->get(route('organization.statutes.index', [$organization->slug, 'q' => 'obveznim']))
            ->assertOk()
            ->assertSee('Zakon o obveznim odnosima')
            ->assertSee('NN 34/2024')
            ->assertSee('nisu redakcijski pročišćeni')
            ->assertSee('Prikaz')
            ->assertSee('od 1')
            ->assertDontSee('pagination.previous', false)
            ->assertDontSee('Showing', false)
            ->assertDontSee('w-5 h-5', false);

        $this->actingAs($user)
            ->get(route('organization.statutes.show', [$organization->slug, $statute->id]))
            ->assertOk()
            ->assertSee('Tekst zakona', false);

        $this->actingAs($user)
            ->post(route('organization.matters.statutes.store', [$organization->slug, $matter->id]), [
                'statute_id' => $statute->id,
            ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('organization.matters.show', [$organization->slug, $matter->id]))
            ->assertOk()
            ->assertSee('Zakon o obveznim odnosima');

        $this->actingAs($user)
            ->delete(route('organization.matters.statutes.destroy', [$organization->slug, $matter->id, $statute->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('matter_statute', [
            'matter_id' => $matter->id,
            'statute_id' => $statute->id,
        ]);
    }

    private function indexCsv(): string
    {
        $header = "Izdanje\tBroj dokumenta\tNaziv dokumenta\tVrsta dokumenta\tCjeloviti dokument/izmjene/dopune/ukinut\tPoveznica";
        $base = "NN 34/2024\t10\tZakon o obveznim odnosima\tzakon\tcjeloviti akt\thttps://narodne-novine.nn.hr/eli/sluzbeni/2024/34/10";
        $regulation = "NN 34/2024\t11\tUredba o cijenama\turedba\tcjeloviti akt\thttps://narodne-novine.nn.hr/eli/sluzbeni/2024/34/11";
        $amendment = "NN 40/2024\t12\tZakon o izmjenama i dopunama Zakona o obveznim odnosima\tzakon\tizmjene i dopune\thttps://narodne-novine.nn.hr/eli/sluzbeni/2024/40/12";

        return $header."\n".$base."\n".$regulation."\n".$amendment."\n";
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
            'city' => 'Zagreb',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$user, $organization];
    }
}
