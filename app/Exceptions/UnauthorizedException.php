<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Thrown when a request is made without valid authentication.
 *
 * Examples:
 * - Missing or expired JWT token
 * - Revoked refresh token
 * - Invalid token signature
 *
 * Maps to HTTP 401 Unauthorized.
 */
class UnauthorizedException extends BaseException
{
    public function __construct(
        string $message = 'Authentication is required to access this resource.',
        array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 401, $context, $previous);
    }
}
