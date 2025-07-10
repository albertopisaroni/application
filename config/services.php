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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'google' => [
        'places_key' => env('GOOGLE_PLACES_KEY'),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
    ],

    'openapi' => [
        'sdi' => [
            'token' => env('OPENAPI_SDI_TOKEN'),
            'url' => env('OPENAPI_SDI_URL'),
            'callback' => [
                'token' => env('OPENAPI_SDI_CALLBACK_TOKEN'),
            ],
        ],
        'company' => [
            'token' => env('OPENAPI_COMPANY_TOKEN'),
            'url' => env('OPENAPI_COMPANY_URL'),
        ],
        'signature' => [
            'token' => env('OPENAPI_SIGNATURE_TOKEN'),
            'url' => env('OPENAPI_SIGNATURE_URL'),
            'key' => env('OPENAPI_SIGNATURE_KEY'),
        ],
        'sms' => [
            'token' => env('OPENAPI_SMS_TOKEN'),
            'url' => env('OPENAPI_SMS_URL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'client_id' => env('STRIPE_CLIENT_ID'),
        'redirect' => env('STRIPE_CONNECT_REDIRECT'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'sms_from' => env('TWILIO_SMS_FROM'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fiscoapi' => [
        'secret' => env('FISCOAPI_SECRET'),
        'base_url' => env('FISCOAPI_BASE_URL', 'https://api.fiscoapi.com/api_esterne'),
    ],

];
