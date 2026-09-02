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

    'osrm' => [
        'url' => env('OSRM_URL', 'http://router.project-osrm.org/route/v1/driving/'),
    ],

    'campus' => [
        'geofence_enabled' => filter_var(env('CAMPUS_GEOFENCE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'latitude' => (float) env('CAMPUS_CENTER_LAT', -0.9525),
        'longitude' => (float) env('CAMPUS_CENTER_LNG', -80.7450),
        'radius_km' => (float) env('CAMPUS_RADIUS_KM', 1.5),
    ],

];
