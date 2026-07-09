<?php

// config for Hwkdo/IntranetAppTeamsBot
return [
    'roles' => [
        'admin' => [
            'name' => 'App-TeamsBot-Admin',
            'permissions' => [
                'see-app-teams-bot',
                'manage-app-teams-bot',
            ],
        ],
        'user' => [
            'name' => 'App-TeamsBot-Benutzer',
            'permissions' => [
                'see-app-teams-bot',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Teams Bot (Benachrichtigungen per 1:1-Chat)
    |--------------------------------------------------------------------------
    |
    | Env: MSGRAPH_TEAMS_BOT_ENABLED, MSGRAPH_TEAMS_BOT_APP_ID,
    |      MSGRAPH_TEAMS_BOT_APP_SECRET, MSGRAPH_TEAMS_APP_CATALOG_ID,
    |      MSGRAPH_TEAMS_BOT_HI_REPLY (optional)
    */
    'bot' => [
        'enabled' => env('MSGRAPH_TEAMS_BOT_ENABLED', false),
        'app_id' => env('MSGRAPH_TEAMS_BOT_APP_ID'),
        'app_secret' => env('MSGRAPH_TEAMS_BOT_APP_SECRET'),
        'teams_app_id' => env('MSGRAPH_TEAMS_APP_CATALOG_ID'),
        'graph_registration' => env('MSGRAPH_TEAMS_BOT_GRAPH_REGISTRATION', 'teams_bot'),
        'service_url_fallback' => 'https://smba.trafficmanager.net/teams/',
        'hi_reply_message' => env(
            'MSGRAPH_TEAMS_BOT_HI_REPLY',
            'Hallo! Schön, dass du da bist. Ich sende dir Benachrichtigungen aus dem HWKDO Intranet.',
        ),
        'auto_reply_message' => 'Dies ist ein Benachrichtigungs-Bot. Bitte bearbeiten Sie Anfragen im Intranet.',
        'mention_help_message' => env(
            'MSGRAPH_TEAMS_BOT_MENTION_HELP',
            'Du kannst mir z. B. schreiben: „@Bot erstelle mir ein Ticket, dass …", um ein Ticket zu erstellen.',
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | teams-sdk-rest (Node.js Teams SDK Wrapper)
    |--------------------------------------------------------------------------
    |
    | Env: TEAMS_SDK_REST_URL, TEAMS_API_KEY, TEAMS_WEBHOOK_SECRET,
    |      TEAMS_SDK_TIMEOUT, TEAMS_WEBHOOK_LOG_REQUESTS (optional)
    */
    'sdk_rest' => [
        'base_url' => env('TEAMS_SDK_REST_URL', 'http://teams-sdk-rest:3978'),
        'api_key' => env('TEAMS_API_KEY'),
        'webhook_secret' => env('TEAMS_WEBHOOK_SECRET'),
        'timeout' => env('TEAMS_SDK_TIMEOUT', 30),
        'log_webhook_requests' => env('TEAMS_WEBHOOK_LOG_REQUESTS', true),
        'log_webhook_payload' => env('TEAMS_WEBHOOK_LOG_PAYLOAD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Teams Activity Feed (Benachrichtigungen im Activity Feed)
    |--------------------------------------------------------------------------
    |
    | Env: MSGRAPH_TEAMS_ACTIVITY_FEED_ENABLED, MSGRAPH_TEAMS_ACTIVITY_FEED_TYPE,
    |      MSGRAPH_TEAMS_ACTIVITY_FEED_TOPIC,
    |      MSGRAPH_TEAMS_ACTIVITY_FEED_WEB_URL (optional),
    |      MSGRAPH_TEAMS_ACTIVITY_FEED_GRAPH_REGISTRATION (Standard: teams_bot)
    */
    'activity_feed' => [
        'enabled' => env('MSGRAPH_TEAMS_ACTIVITY_FEED_ENABLED', false),
        'activity_type' => env('MSGRAPH_TEAMS_ACTIVITY_FEED_TYPE', 'systemDefault'),
        'topic_title' => env('MSGRAPH_TEAMS_ACTIVITY_FEED_TOPIC', 'HWKDO Intranet'),
        'topic_web_url' => env('MSGRAPH_TEAMS_ACTIVITY_FEED_WEB_URL'),
        'graph_registration' => env('MSGRAPH_TEAMS_ACTIVITY_FEED_GRAPH_REGISTRATION', 'teams_bot'),
    ],
];
