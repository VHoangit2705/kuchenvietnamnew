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
   // VietQR public API configuration
    'vietqr' => [
        'banks_url' => env('VIETQR_BANKS_URL', 'https://api.vietqr.io/v2/banks'),
        'account_no' => env('VIETQR_ACCOUNT_NO', '116615609999'),
        'account_name' => env('VIETQR_ACCOUNT_NAME'),
        'bank_id' => env('VIETQR_BANK_ID'),
        'template' => env('VIETQR_TEMPLATE', 'compact'),
        'bank_name' => env('VIETQR_BANK_NAME', 'VietinBank'),
    ],
    
     // Warranty Image Base URL configuration
    'warranty' => [
        'image_base_url' => env('WARRANTY_IMAGE_BASE_URL', 'https://kuchenvietnam.vn/kuchen/trungtambaohanhs/storage/app/public'),
    ],

];
