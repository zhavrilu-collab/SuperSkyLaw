<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAreasTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_and_documents_use_page_tabs_instead_of_submenus(): void
    {
        [$owner, $organization] = $this->office();

        $invoices = $this->actingAs($owner)->get(route('organization.invoices.index', $organization->slug));
        $invoices->assertOk();
        $this->assertModule($invoices->getContent(), 'Financije', 'Računi');
        $invoices->assertSee('Sati', false)
            ->assertSee('Tarifa HOK', false)
            ->assertSee('Troškovnik', false)
            ->assertSee('Izvještaj', false)
            ->assertSee('Depozit', false);

        $time = $this->actingAs($owner)->get(route('organization.time.index', $organization->slug));
        $time->assertOk();
        $this->assertModule($time->getContent(), 'Financije', 'Sati');

        $documents = $this->actingAs($owner)->get(route('organization.documents.index', $organization->slug));
        $documents->assertOk();
        $this->assertModule($documents->getContent(), 'Dokumenti', 'Mape');
        $documents->assertSee('Predlošci', false);

        $templates = $this->actingAs($owner)->get(route('organization.templates.index', $organization->slug));
        $templates->assertOk();
        $this->assertModule($templates->getContent(), 'Dokumenti', 'Predlošci');
    }

    private function assertModule(string $html, string $menu, string $active): void
    {
        preg_match('/<nav class="app-sidebar-nav"[^>]*>(.*?)<\/nav>/s', $html, $nav);
        $this->assertArrayHasKey(1, $nav);
        $this->assertStringNotContainsString('app-sidebar-submenu', $nav[1]);
        $this->assertStringNotContainsString('app-sidebar-group', $nav[1]);
        foreach (['Računi', 'Sati', 'Tarifa HOK', 'Troškovnik', 'Izvještaj', 'Depozit', 'Mape', 'Predlošci'] as $label) {
            $this->assertDoesNotMatchRegularExpression('/>\s*'.preg_quote($label, '/').'\s*<\/a>/', $nav[1]);
        }
        $this->assertMatchesRegularExpression('/class="app-sidebar-link\s+active\s*"[^>]*>.*?'.preg_quote($menu, '/').'<\/a>/s', $nav[1]);
        $this->assertMatchesRegularExpression('/class="nav-link active"[^>]*>'.preg_quote($active, '/').'<\/a>/', $html);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function office(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Ured Horvat',
            'slug' => 'ured-horvat-podrucja',
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
}
