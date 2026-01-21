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
    'pusher' => [
        'app_id' => env('PUSHER_APP_ID'),
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'cluster' => env('PUSHER_APP_CLUSTER'),
    ],
    'whatsapp' => [
        'base_url' => env('WHATSAPP_BASE_URL'),
        'api_key' => env('WHATSAPP_API_KEY'),
        'bot_number' => env('WHATSAPP_BOT_NUMBER'),
        'participants' => array_filter(array_map('trim', explode(',', env('WHATSAPP_PARTICIPANTS', '')))),
        'round_robin' => filter_var(env('WHATSAPP_ROUND_ROBIN', false), FILTER_VALIDATE_BOOLEAN),
        'instance' => env('WHATSAPP_INSTANCE'),
    ],

    'whatsapp' => [
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
        'signature_header' => env('WHATSAPP_SIGNATURE_HEADER', 'X-Webhook-Signature'),
        'base_url' => env('WHATSAPP_BASE_URL'),
        'api_key' => env('WHATSAPP_API_KEY'),
        'group_api_key' => env('GROUP_API_KEY'),
    ],

];
