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

    'api_sports' => [
        'key' => env('API_SPORTS_KEY'),
        'urls' => [
            'football' => env('API_SPORTS_FOOTBALL_URL', 'https://v3.football.api-sports.io'),
            'nba' => env('API_SPORTS_NBA_URL', 'https://v2.nba.api-sports.io'),
            'nfl' => env('API_SPORTS_NFL_URL', 'https://v1.american-football.api-sports.io'),
            'baseball' => env('API_SPORTS_BASEBALL_URL', 'https://v1.baseball.api-sports.io'),
            'hockey' => env('API_SPORTS_HOCKEY_URL', 'https://v1.hockey.api-sports.io'),
            'basketball' => env('API_SPORTS_BASKETBALL_URL', 'https://v1.basketball.api-sports.io'),
        ],
    ],

'news_api' => [
        'key' => env('NEWS_API_KEY'),
    ],

    'sportsdb' => [
        'key' => env('SPORTSDB_API_KEY', '3'), // free demo key
        'base_url' => env('SPORTSDB_BASE_URL', 'https://www.thesportsdb.com/api/v1/json'),
    ],

    'ncaa_api' => [
        'base_url' => env('NCAA_API_BASE_URL', 'https://ncaa-api.henrygd.me'),
    ],

];
