<?php

declare(strict_types=1);

namespace App\Support\DTOs;

/**
 * Base class for all DTOs in the application.
 *
 * DTOs are read-only value objects. They carry validated data from
 * the HTTP layer (Form Requests) into the Service layer.
 *
 * Rules:
 * - All properties must be readonly.
 * - No setters. No mutation after construction.
 * - DTOs should have a static fromRequest() factory on concrete classes.
 */
abstract class BaseDTO
{
    /**
     * Convert the DTO to a plain array.
     * Useful for logging and debugging (never for DB writes).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
