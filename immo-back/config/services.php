<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Front Next.js (immo-front) : liens « Voir le site » et revalidation du cache après une modification du contenu.
    'front' => [
        'url' => rtrim(env('FRONT_URL', 'http://localhost:3000'), '/'),
        'revalidate_secret' => env('FRONT_REVALIDATE_SECRET'),
        // Clé envoyée par le serveur Next (en-tête X-Front-Key) pour transmettre les formulaires du site.
        'api_key' => env('FRONT_API_KEY'),
    ],

    // Demandes reçues depuis le site : email à l'équipe et workflows n8n (facultatifs).
    'leads' => [
        'notify_email' => env('LEADS_NOTIFY_EMAIL'),
        'quote_webhook' => env('N8N_QUOTE_WEBHOOK_URL'),
        'booking_webhook' => env('N8N_BOOKING_WEBHOOK_URL'),
        'message_webhook' => env('N8N_MESSAGE_WEBHOOK_URL'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
