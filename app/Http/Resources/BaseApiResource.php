<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base resource class for all API resources.
 *
 * Enforces that all resources define toArray() explicitly.
 * Disables the default data wrapping to avoid double-wrapping
 * since ApiResponse already provides the envelope.
 */
abstract class BaseApiResource extends JsonResource
{
    /**
     * Disable the default 'data' wrapper.
     * ApiResponse provides the envelope — resources should return their fields directly.
     */
    public static $wrap = null;

    /**
     * All concrete resources must implement this.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(Request $request): array;
}
