<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Thrown when an authenticated user attempts an action they are not permitted to perform.
 *
 * Examples:
 * - Regular user trying to access creator-only endpoints
 * - User trying to update another user's profile
 * - Non-admin accessing admin routes
 *
 * Maps to HTTP 403 Forbidden.
 */
class ForbiddenException extends BaseException
{
    public function __construct(
        string $message = 'You do not have permission to perform this action.',
        array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 403, $context, $previous);
    }
}
