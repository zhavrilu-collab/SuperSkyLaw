<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Support\OfficeThemes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfficeThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_one_of_the_five_theme_colors(): void
    {
        [$owner, $organization] = $this->office();

        $this->actingAs($owner)
            ->get(route('organization.settings.appearance', $organization->slug))
            ->assertOk()
            ->assertDontSee('type="color"', false)
            ->assertDontSee('office-theme-option', false)
            ->assertSee('tema-svatch', false)
            ->assertSee('data-tema="zelena"', false)
            ->assertSee('data-tema="plava"', false)
            ->assertSee('data-tema="crvena"', false)
            ->assertSee('data-tema="zuta"', false)
            ->assertSee('data-tema="narancasta"', false)
            ->assertSee('data-stil="kreda"', false)
            ->assertSee('data-stil="obrub"', false)
            ->assertSee('data-stil="pruga"', false)
            ->assertSee('data-stil="sjena"', false)
            ->assertSee('data-stil="slovo"', false)
            ->assertDontSee('data-stil="noc"', false)
            ->assertSee('Početna', false)
            ->assertDontSee('data-stil="tiha"', false)
            ->assertDontSee('data-stil="obrnuto"', false)
            ->assertSee('BOJA TEME');

        $this->actingAs($owner)
            ->get(route('organization.settings.edit', $organization->slug))
            ->assertOk()
            ->assertSee('Osnovni podaci', false)
            ->assertSee('Tim', false)
            ->assertDontSee('BOJA TEME', false);

        $this->actingAs($owner)
            ->put(route('organization.settings.theme', $organization->slug), ['theme_color' => 'plava'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('plava', $organization->fresh()->theme_color);
        $this->assertNull($organization->fresh()->theme_style);

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('brand/product/plava-horizontal.png', false)
            ->assertSee('#50abde', false);

        $this->actingAs($owner)
            ->put(route('organization.settings.theme', $organization->slug), [
                'theme_color' => 'zelena',
                'theme_style' => 'kreda',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('kreda', $organization->fresh()->theme_style);

        $this->actingAs($owner)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk()
            ->assertSee('#5b651f', false)
            ->assertSee('brand/product/zelena-horizontal.png', false);

        $this->actingAs($owner)
            ->from(route('organization.settings.appearance', $organization->slug))
            ->put(route('organization.settings.theme', $organization->slug), [
                'theme_color' => 'zelena',
                'theme_style' => 'obrnuto',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('theme_style');

        $this->assertSame('kreda', $organization->fresh()->theme_style);
    }

    public function test_settings_reject_colors_outside_the_palette(): void
    {
        [$owner, $organization] = $this->office();

        foreach (['bordo', 'ljubicasta', '#ff00ff', 'white', ''] as $color) {
            $this->actingAs($owner)
                ->from(route('organization.settings.appearance', $organization->slug))
                ->put(route('organization.settings.theme', $organization->slug), ['theme_color' => $color])
                ->assertRedirect(route('organization.settings.appearance', $organization->slug))
                ->assertSessionHasErrors('theme_color');
        }

        $this->assertSame('zelena', $organization->fresh()->theme_color);
    }

    public function test_guest_pages_use_the_vertical_logo_for_the_known_office(): void
    {
        [$owner, $organization] = $this->office();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('brand/product/zelena.png', false);

        $this->get(route('register.organization'))
            ->assertOk()
            ->assertSee('brand/product/zelena.png', false);

        $organization->update(['theme_color' => 'crvena']);

        $this->get(route('portal.login', $organization->slug))
            ->assertOk()
            ->assertSee('brand/product/crvena.png', false)
            ->assertDontSee('brand/product/zelena.png', false);

        DB::table('organizations')->where('id', $organization->id)->update(['theme_color' => 'bordo']);
        $organization->refresh();

        $this->assertSame('zelena', $organization->themeColor());
        $this->assertSame(OfficeThemes::DEFAULT, OfficeThemes::resolve('bordo'));
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function office(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ured Horvat',
            'slug' => 'ured-horvat-tema',
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
            'email' => 'ured@horvat.test',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => OrganizationRole::Owner,
        ]);

        return [$owner, $organization];
    }

    /**
     * @return array<string, string>
     */
    private function payload(string $themeColor): array
    {
        return [
            'name' => 'Ured Horvat',
            'email' => 'ured@horvat.test',
            'theme_color' => $themeColor,
        ];
    }
}
