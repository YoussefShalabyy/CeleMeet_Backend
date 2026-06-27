<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Thrown when a business rule is violated.
 *
 * Examples:
 * - Insufficient Coins balance
 * - Creator has messaging disabled
 * - Subscription already active
 *
 * Always maps to HTTP 422 Unprocessable Entity.
 * This is NOT a validation error — it is a domain/logic error.
 */
class BusinessException extends BaseException
{
    public function __construct(
        string $message = 'A business rule violation occurred.',
        array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 422, $context, $previous);
    }
}
