<?php

return [

    'webhook_url' => env('ADMIN_CONSOLE_WEBHOOK_URL'),
    'webhook_secret' => env('ADMIN_CONSOLE_WEBHOOK_SECRET'),
    'api_url' => env('ADMIN_CONSOLE_API_URL', 'http://127.0.0.1:8001'),
    'application_slug' => env('ADMIN_CONSOLE_APPLICATION_SLUG', 'legal-saas'),

];
