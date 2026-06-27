<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Helper for building standardized pagination metadata.
 *
 * Ensures all paginated API responses have a consistent meta structure.
 */
final class PaginationHelper
{
    /**
     * Extract pagination metadata from a LengthAwarePaginator instance.
     *
     * @return array{
     *     current_page: int,
     *     per_page: int,
     *     total: int,
     *     last_page: int,
     *     from: int|null,
     *     to: int|null,
     *     has_more_pages: bool
     * }
     */
    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];
    }

    /**
     * Default per-page count used across all paginated endpoints.
     */
    public static function defaultPerPage(): int
    {
        return 15;
    }

    /**
     * Clamp a per_page request value to prevent abuse.
     */
    public static function clampPerPage(int $requested, int $max = 50): int
    {
        return min(max(1, $requested), $max);
    }
}
