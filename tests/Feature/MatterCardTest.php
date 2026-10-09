<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\Organization;
use App\Models\OrganizationUser;
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
