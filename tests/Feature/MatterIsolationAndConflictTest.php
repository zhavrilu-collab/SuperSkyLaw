<?php

namespace Tests\Feature;

use App\Enums\ConflictResult;
use App\Enums\MatterStatus;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Matter;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterIsolationAndConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_office_cannot_see_another_offices_matter(): void
    {
        [$userA, $orgA] = $this->office('ured-a', 'Ured A');
        [$userB, $orgB] = $this->office('ured-b', 'Ured B');

        $this->actingAs($userA)->post(route('organization.matters.store', $orgA->slug), $this->matterPayload('Predmet A', '12345678903'))
            ->assertRedirect();

        $matter = Matter::query()->where('organization_id', $orgA->id)->first();
        $this->assertNotNull($matter);

        $this->actingAs($userB)
            ->get(route('organization.matters.show', [$orgB->slug, $matter->id]))
            ->assertNotFound();

        $this->actingAs($userB)
            ->get(route('organization.matters.index', $orgB->slug))
            ->assertOk()
            ->assertDontSee('Predmet A');
    }

    public function test_hard_conflict_requires_partner_acknowledgement(): void
    {
        [$user, $org] = $this->office('ured-sukob', 'Ured Sukob');

        $this->actingAs($user)->post(route('organization.matters.store', $org->slug), $this->matterPayload('Prvi predmet', '12345678903'));

        $this->actingAs($user)
            ->post(route('organization.matters.store', $org->slug), $this->matterPayload('Drugi predmet', '10987654326', '12345678903'))
            ->assertRedirect()
            ->assertSessionHas('conflict');

        $this->assertSame(1, Matter::query()->where('organization_id', $org->id)->count());

        $this->actingAs($user)
            ->post(route('organization.matters.store', $org->slug), $this->matterPayload('Drugi predmet', '10987654326', '12345678903', true))
            ->assertRedirect();

        $second = Matter::query()->where('title', 'Drugi predmet')->first();
        $this->assertNotNull($second);
        $this->assertSame(ConflictResult::Hard, $second->conflictChecks()->first()->result);
        $this->assertSame(MatterStatus::Active, $second->status);
    }

    public function test_lawyer_does_not_see_unassigned_matter(): void
    {
        [$partner, $org] = $this->office('ured-vidljivost', 'Ured Vidljivost');
        $this->actingAs($partner)->post(route('organization.matters.store', $org->slug), array_merge(
            $this->matterPayload('Samo partner', '12345678903'),
            ['assignee_ids' => [$partner->id]],
        ));

        $lawyer = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $org->id,
            'user_id' => $lawyer->id,
            'role' => OrganizationRole::Lawyer,
        ]);

        $matter = Matter::query()->first();
        $matter->assignees()->sync([$partner->id]);

        $this->actingAs($lawyer)
            ->get(route('organization.matters.show', [$org->slug, $matter->id]))
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function office(string $slug, string $name): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => $name,
            'slug' => $slug,
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
    private function matterPayload(string $title, string $clientOib, ?string $opposingOib = null, bool $acknowledge = false): array
    {
        return [
            'title' => $title,
            'kind' => 'civil',
            'status' => 'active',
            'billing_method' => 'hourly',
            'hourly_rate' => 100,
            'client_name' => 'Klijent '.$title,
            'client_oib' => $clientOib,
            'client_kind' => 'person',
            'opposing_name' => $opposingOib ? 'Protivna '.$title : null,
            'opposing_oib' => $opposingOib,
            'acknowledge_conflict' => $acknowledge ? '1' : '0',
            'conflict_note' => $acknowledge ? 'Partner je razmotrio sukob.' : null,
        ];
    }
}
