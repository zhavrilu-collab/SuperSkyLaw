<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Matter;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Statute;
use App\Models\User;
use App\Services\StatuteImporter;
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
            if (str_contains($url, '/api/editions')) {
                return Http::response([34]);
            }
            if (str_contains($url, '/api/acts')) {
                return Http::response(['10', '11']);
            }
            if (str_contains($url, '/api/act')) {
                $act = $request->data()['act_num'] ?? '';

                return Http::response($act === '10' ? $this->lawGraph() : $this->regulationGraph());
            }

            return Http::response('<html><body><p>Tekst zakona o obveznim odnosima</p><script>alert(1)</script></body></html>');
        });

        app(StatuteImporter::class)->pull(10);

        $statute = Statute::query()->first();
        $this->assertNotNull($statute);
        $this->assertSame(1, Statute::query()->count());
        $this->assertSame('Zakon o obveznim odnosima', $statute->title);
        $this->assertSame('NN 34/2024', $statute->citation);
        $this->assertStringContainsString('Tekst zakona o obveznim odnosima', (string) $statute->text_plain);
        $this->assertStringNotContainsString('script', (string) $statute->text_html);

        Http::fake();
        app(StatuteImporter::class)->pull(5);
        Http::assertNothingSent();
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

        $this->actingAs($user)
            ->get(route('organization.statutes.index', [$organization->slug, 'q' => 'obveznim']))
            ->assertOk()
            ->assertSee('Zakon o obveznim odnosima')
            ->assertSee('nisu redakcijski pročišćeni');

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

    /**
     * @return list<array<string, mixed>>
     */
    private function lawGraph(): array
    {
        return [
            [
                '@id' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2024/34/10',
                'http://data.europa.eu/eli/ontology#type_document' => [
                    ['@id' => 'https://narodne-novine.nn.hr/resource/authority/document-type/ZAKON'],
                ],
                'http://data.europa.eu/eli/ontology#date_publication' => [
                    ['@value' => '2024-03-27'],
                ],
            ],
            [
                '@id' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2024/34/10/hrv',
                'http://data.europa.eu/eli/ontology#title' => [
                    ['@value' => 'Zakon o obveznim odnosima'],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function regulationGraph(): array
    {
        return [
            [
                '@id' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2024/34/11',
                'http://data.europa.eu/eli/ontology#type_document' => [
                    ['@id' => 'https://narodne-novine.nn.hr/resource/authority/document-type/UREDBA'],
                ],
            ],
            [
                '@id' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2024/34/11/hrv',
                'http://data.europa.eu/eli/ontology#title' => [
                    ['@value' => 'Uredba o cijenama'],
                ],
            ],
        ];
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
