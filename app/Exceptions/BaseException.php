<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all application-level exceptions.
 *
 * Provides a structured context array for logging and response serialization.
 * All custom exceptions in this project extend this class.
 */
abstract class BaseException extends Exception
{
    /**
     * @param  string  $message  Human-readable error message.
     * @param  int  $httpStatusCode  The HTTP status code this maps to.
     * @param  array<string, mixed>  $context  Structured context for logging.
     * @param  Throwable|null  $previous  The original exception, if wrapping one.
     */
    public function __construct(
        string $message = '',
        protected readonly int $httpStatusCode = 500,
        protected readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
