<?php

return [

    'webhook_url' => env('ADMIN_CONSOLE_WEBHOOK_URL'),
    'webhook_secret' => env('ADMIN_CONSOLE_WEBHOOK_SECRET'),
    'api_url' => env('ADMIN_CONSOLE_API_URL', 'http://127.0.0.1:8001'),
    'application_slug' => env('ADMIN_CONSOLE_APPLICATION_SLUG', 'legal-saas'),

    'http_verify' => filter_var(env('ADMIN_CONSOLE_HTTP_VERIFY', true), FILTER_VALIDATE_BOOL),

    /*
    | Force console HTTPS calls to resolve to 127.0.0.1 when this app
    | and the admin console share a VPS (avoids hairpin TLS).
    */
    'http_resolve_loopback' => filter_var(env('ADMIN_CONSOLE_HTTP_RESOLVE_LOOPBACK', false), FILTER_VALIDATE_BOOL),

];
