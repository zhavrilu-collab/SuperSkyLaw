<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\CourtEvent;
use App\Models\Matter;
use App\Models\MatterNote;
use App\Models\MatterStage;
use App\Models\OfficeTask;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\TimelineEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_window_stores_each_kind_and_can_leave_the_stage_empty(): void
    {
        [$user, $org] = $this->office();
        $matter = $this->matter($org, $user);
        $stage = MatterStage::query()->create([
            'organization_id' => $org->id,
            'matter_id' => $matter->id,
            'name' => 'Priprema',
            'color' => 'lime',
            'started_on' => '2026-10-01',
            'position' => 1,
        ]);

        $this->actingAs($user)->post(route('organization.entries.store', $org->slug), [
            'kind' => 'termin',
            'title' => 'Ročište o glavnoj stvari',
            'type' => 'hearing',
            'matter_id' => $matter->id,
            'stage_id' => $stage->id,
            'starts_at' => '2026-10-20 10:00:00',
            'responsible_user_id' => $user->id,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('organization.entries.store', $org->slug), [
            'kind' => 'zadatak',
            'title' => 'Pripremi podnesak',
            'matter_id' => $matter->id,
            'user_id' => $user->id,
            'due_at' => '2026-10-21 09:00:00',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('organization.entries.store', $org->slug), [
            'kind' => 'zapis',
            'matter_id' => $matter->id,
            'stage_id' => $stage->id,
            'type' => 'letter',
            'body' => 'Dopis sudu.',
            'occurred_at' => '2026-10-15 11:00:00',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('organization.entries.store', $org->slug), [
            'kind' => 'biljeska',
            'matter_id' => $matter->id,
            'body' => 'Interna bilješka uz dan.',
            'noted_on' => '2026-10-15',
        ])->assertRedirect();

        $this->assertDatabaseHas('court_events', [
            'title' => 'Ročište o glavnoj stvari',
            'stage_id' => $stage->id,
        ]);
        $this->assertNull(OfficeTask::query()->first()->stage_id);
        $this->assertSame($stage->id, TimelineEntry::query()->first()->stage_id);
        $this->assertSame('2026-10-15', MatterNote::query()->first()->noted_on->toDateString());
        $this->assertNull(MatterNote::query()->first()->stage_id);
    }

    public function test_calendar_page_keeps_the_grid_and_moves_ics_to_settings(): void
    {
        [$user, $org] = $this->office();
        CourtEvent::query()->create([
            'organization_id' => $org->id,
            'type' => 'hearing',
            'title' => 'Odvod na gradilištu',
            'starts_at' => '2026-10-16 10:00:00',
            'responsible_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('organization.calendar.index', $org->slug))
            ->assertOk()
            ->assertSee('id="kalendarPanel"', false)
            ->assertSee('kalendar-split', false)
            ->assertSee('flex-direction: row', false)
            ->assertSee('id="unosStavkeProzor"', false)
            ->assertDontSee('Vanjski kalendar', false);

        $this->actingAs($user)
            ->get(route('organization.settings.calendar', $org->slug))
            ->assertForbidden();

        $org->update(['plan' => 'premium']);

        $this->actingAs($user)
            ->get(route('organization.settings.calendar', $org->slug))
            ->assertOk()
            ->assertSee('Vanjski kalendar', false)
            ->assertSee('Preuzmi ICS', false)
            ->assertSee('>Kalendar</a>', false);
    }

    public function test_task_can_be_completed_and_records_need_matter_permission(): void
    {
        [$owner, $org] = $this->office();
        $secretary = User::factory()->create();
        OrganizationUser::query()->create([
            'organization_id' => $org->id,
            'user_id' => $secretary->id,
            'role' => OrganizationRole::Secretary,
        ]);
        $task = OfficeTask::query()->create([
            'organization_id' => $org->id,
            'user_id' => $secretary->id,
            'title' => 'Poslati dopis',
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($secretary)
            ->post(route('organization.entries.store', $org->slug), [
                'kind' => 'zapis',
                'matter_id' => 1,
                'type' => 'letter',
                'body' => 'Ne smije.',
                'occurred_at' => now()->toDateTimeString(),
            ])->assertForbidden();

        $this->actingAs($owner)
            ->post(route('organization.tasks.complete', [$org->slug, $task->id]))
            ->assertRedirect();

        $this->assertNotNull($task->fresh()->completed_at);
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

    private function matter(Organization $organization, User $user): Matter
    {
        $matter = Matter::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Predmet',
            'internal_number' => '2026/001',
            'kind' => 'civil',
            'status' => 'active',
            'billing_method' => 'hourly',
            'created_by_user_id' => $user->id,
        ]);
        $matter->assignees()->sync([$user->id]);

        return $matter;
    }
}
