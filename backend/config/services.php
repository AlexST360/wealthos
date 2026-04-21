<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Servicios de Terceros
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    // ── Groq (IA) ─────────────────────────────────────────────────────────
    'groq' => [
        'key'   => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    // ── CMF Chile ─────────────────────────────────────────────────────────
    'cmf' => [
        'key' => env('CMF_API_KEY'),
    ],

    // ── Microservicio Python Yahoo Finance (opcional) ──────────────────────
    'yahoo_finance' => [
        'url' => env('YAHOO_FINANCE_SERVICE_URL'),
    ],

];
