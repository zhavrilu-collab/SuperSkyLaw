<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sudreg' => [
        'base_url' => env('SUDREG_BASE_URL', 'https://sudreg-data.gov.hr/api'),
        'client_id' => env('SUDREG_CLIENT_ID'),
        'client_secret' => env('SUDREG_CLIENT_SECRET'),
    ],

    'moj_eracun' => [
        'url' => env('MOJ_ERACUN_URL'),
        'username' => env('MOJ_ERACUN_USERNAME'),
        'password' => env('MOJ_ERACUN_PASSWORD'),
    ],

    'esign' => [
        'url' => env('ESIGN_URL'),
        'username' => env('ESIGN_USERNAME'),
        'password' => env('ESIGN_PASSWORD'),
    ],

    'sms' => [
        'url' => env('SMS_URL'),
        'token' => env('SMS_TOKEN'),
        'cost_cents' => (int) env('SMS_COST_CENTS', 8),
    ],

    'nn' => [
        'base_url' => env('NN_BASE_URL', 'https://narodne-novine.nn.hr'),
        'ca_bundle' => env('NN_CA_BUNDLE'),
    ],

];
