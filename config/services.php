<?php

return [

      'medicine_api' => [
       'base_url' => env('MEDICINE_API_BASE_URL', 'http://127.0.0.1:8001'),
       'api_key'  => env('MEDICINE_API_KEY'),
       'timeout'  => env('MEDICINE_API_TIMEOUT', 15),
   ],


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

    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY', 'AIzaSyCtxBnw5jv06A58wzB9WfNbY35O0XxNcNc'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'pharmacymanagesystem.firebaseapp.com'),
        'database_url' => env('FIREBASE_DATABASE_URL', 'https://pharmacymanagesystem-default-rtdb.firebaseio.com'),
        'project_id' => env('FIREBASE_PROJECT_ID', 'pharmacymanagesystem'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'pharmacymanagesystem.firebasestorage.app'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', '227901150233'),
        'app_id' => env('FIREBASE_APP_ID', '1:227901150233:web:591a4ea18dc3b4e7d84688'),
        'measurement_id' => env('FIREBASE_MEASUREMENT_ID', 'G-4G8B6Y9X28'),
    ],

];
