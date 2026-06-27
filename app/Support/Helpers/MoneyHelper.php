<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use InvalidArgumentException;

/**
 * Helper for Coin arithmetic operations.
 *
 * IMPORTANT: Coins are always stored and computed as integers (BIGINT).
 * Never use floats for Coin math. This class enforces that invariant.
 */
final class MoneyHelper
{
    /**
     * Calculate the platform commission deduction from an earnings amount.
     *
     * @param  int  $grossCoins  The total coins earned before commission.
     * @param  float|null  $rate  Commission rate (0.0 to 1.0). Defaults to config value.
     * @return int The commission amount in coins (floored).
     */
    public static function calculateCommission(int $grossCoins, ?float $rate = null): int
    {
        $rate ??= (float) config('wallet.commission_rate', 0.20);

        return (int) floor($grossCoins * $rate);
    }

    /**
     * Calculate the net coins a creator receives after platform commission.
     */
    public static function netAfterCommission(int $grossCoins, ?float $rate = null): int
    {
        return $grossCoins - self::calculateCommission($grossCoins, $rate);
    }

    /**
     * Calculate the cost of a call based on duration.
     *
     * Duration is rounded UP to the nearest minute (ceiling).
     *
     * @param  int  $durationSeconds  Actual call duration in seconds.
     * @param  int  $ratePerMinute  Creator's configured coins per minute.
     * @return int Total coins to charge.
     */
    public static function calculateCallCost(int $durationSeconds, int $ratePerMinute): int
    {
        if ($durationSeconds <= 0) {
            return 0;
        }

        $minutes = (int) ceil($durationSeconds / 60);

        return $minutes * $ratePerMinute;
    }

    /**
     * Calculate the coins equivalent of a real-money amount.
     *
     * @param  float  $amount  The real-money amount.
     * @param  int|null  $coinsPerUnit  Coins per currency unit. Defaults to config value.
     */
    public static function fromCurrency(float $amount, ?int $coinsPerUnit = null): int
    {
        $coinsPerUnit ??= (int) config('wallet.coins_per_currency_unit', 100);

        return (int) floor($amount * $coinsPerUnit);
    }

    /**
     * Ensure a coin amount is non-negative.
     * Throws a domain-level error if negative coins are provided.
     *
     * @throws InvalidArgumentException
     */
    public static function assertPositive(int $coins, string $context = 'Coin amount'): void
    {
        if ($coins < 0) {
            throw new InvalidArgumentException("{$context} must be non-negative. Got: {$coins}");
        }
    }
}
