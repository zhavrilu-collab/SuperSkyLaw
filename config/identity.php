<?php

return [

    'core_auth_enabled' => (bool) env('IDENTITY_CORE_AUTH_ENABLED', false),

    'core_api_url' => rtrim((string) env('IDENTITY_CORE_API_URL', env('ADMIN_CONSOLE_API_URL', 'http://127.0.0.1:8001')), '/'),

    'application_slug' => env('ADMIN_CONSOLE_APPLICATION_SLUG', 'legal-saas'),

    'sync_secret' => env('ADMIN_CONSOLE_WEBHOOK_SECRET'),

    'core_api_version' => env('IDENTITY_CORE_API_VERSION', 'v1'),

    'google_oauth_enabled' => (bool) env('IDENTITY_GOOGLE_OAUTH_ENABLED', false),

    'microsoft_oauth_enabled' => (bool) env('IDENTITY_MICROSOFT_OAUTH_ENABLED', false),

    'unified_login_enabled' => (bool) env('IDENTITY_UNIFIED_LOGIN_ENABLED', false),

];
