<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Thrown when a requested resource cannot be found.
 *
 * Examples:
 * - Creator profile not found
 * - Post not found or soft-deleted
 * - Coin package does not exist
 *
 * Maps to HTTP 404 Not Found.
 */
class NotFoundException extends BaseException
{
    public function __construct(
        string $message = 'The requested resource was not found.',
        array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 404, $context, $previous);
    }
}
