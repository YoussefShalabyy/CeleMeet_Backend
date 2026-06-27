<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Paymob API Key
    |--------------------------------------------------------------------------
    */
    'api_key' => env('PAYMOB_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Paymob HMAC Secret
    |--------------------------------------------------------------------------
    | Used to verify the authenticity of Paymob webhook callbacks.
    | Never skip HMAC verification in production.
    */
    'hmac_secret' => env('PAYMOB_HMAC_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Integration IDs
    |--------------------------------------------------------------------------
    | Each payment method has its own integration ID in Paymob.
    */
    'integrations' => [
        'card' => (int) env('PAYMOB_INTEGRATION_CARD', 0),
        'wallet' => (int) env('PAYMOB_INTEGRATION_WALLET', 0),
        'kiosk' => (int) env('PAYMOB_INTEGRATION_KIOSK', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Iframe IDs
    |--------------------------------------------------------------------------
    | Used for the Paymob hosted payment page.
    */
    'iframe_ids' => [
        'card' => (int) env('PAYMOB_IFRAME_CARD', 0),
        'wallet' => (int) env('PAYMOB_IFRAME_WALLET', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com/api'),

    /*
    |--------------------------------------------------------------------------
    | API Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('PAYMOB_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    | ISO 4217 currency code for transactions.
    */
    'currency' => env('PAYMOB_CURRENCY', 'EGP'),

    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    | The URL Paymob will POST the transaction result to.
    */
    'callback_url' => env('PAYMOB_CALLBACK_URL', '/api/v1/payments/callback'),

];
