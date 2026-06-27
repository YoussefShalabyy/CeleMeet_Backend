<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Support\Helpers\PaginationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Centralized API response builder.
 *
 * ALL API responses must be built through this class.
 * This guarantees a single, consistent response envelope across the entire API.
 *
 * Response shape:
 * {
 *   "success": bool,
 *   "message": string,
 *   "data": object|array|null,
 *   "meta": object|null,
 *   "errors": object|null
 * }
 */
final class ApiResponse
{
    /**
     * Return a successful response.
     *
     * @param  mixed  $data  The response payload (Resource, array, or null).
     * @param  string  $message  Human-readable success message.
     * @param  int  $code  HTTP status code (default: 200).
     * @param  array<string, mixed>|null  $meta  Additional metadata (e.g., pagination).
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        ?array $meta = null,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'errors' => null,
        ], $code);
    }

    /**
     * Return an error response.
     *
     * @param  string  $message  Human-readable error message.
     * @param  int  $code  HTTP status code.
     * @param  array<string, mixed>|null  $errors  Structured validation or field errors.
     */
    public static function error(
        string $message = 'An error occurred',
        int $code = 400,
        ?array $errors = null,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => null,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Return a paginated successful response.
     *
     * Automatically extracts pagination metadata from a LengthAwarePaginator.
     *
     * @param  mixed  $data  Already-transformed collection/array.
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        mixed $data,
        string $message = 'Success',
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            meta: PaginationHelper::meta($paginator),
        );
    }

    /**
     * Return a 201 Created response.
     */
    public static function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return self::success(data: $data, message: $message, code: 201);
    }

    /**
     * Return a 204 No Content response.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
