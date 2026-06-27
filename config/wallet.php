<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Commission Rate
    |--------------------------------------------------------------------------
    | The platform takes this percentage of every creator earning.
    | Value: 0.20 = 20%
    */
    'commission_rate' => (float) env('WALLET_COMMISSION_RATE', 0.20),

    /*
    |--------------------------------------------------------------------------
    | Minimum Withdrawal Amount (in Coins)
    |--------------------------------------------------------------------------
    */
    'min_withdrawal_coins' => (int) env('WALLET_MIN_WITHDRAWAL_COINS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Coins to Currency Ratio
    |--------------------------------------------------------------------------
    | How many coins equal 1 unit of the base currency (USD).
    | Example: 100 coins = $1.00
    */
    'coins_per_currency_unit' => (int) env('WALLET_COINS_PER_UNIT', 100),

    /*
    |--------------------------------------------------------------------------
    | Message Refund Window (Hours)
    |--------------------------------------------------------------------------
    | How many hours after sending a message a user can request a refund
    | if the creator has not replied.
    */
    'message_refund_window_hours' => (int) env('WALLET_MESSAGE_REFUND_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Minimum Call Hold (Minutes)
    |--------------------------------------------------------------------------
    | Minimum minutes of coins to hold before a call can begin.
    */
    'min_call_hold_minutes' => (int) env('WALLET_MIN_CALL_HOLD_MINUTES', 1),

];
