<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class UnifiedLoginRedirectTest extends TestCase
{
    public function test_login_page_stays_on_law_when_unified_login_enabled(): void
    {
        config([
            'identity.core_auth_enabled' => true,
            'identity.unified_login_enabled' => true,
            'identity.core_api_url' => 'http://127.0.0.1:8001',
            'identity.application_slug' => 'legal-saas',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Prijava — SuperSkyLaw', false)
            ->assertDontSee('http://127.0.0.1:8001/platform/prijava', false);
    }
}
