<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base resource class for all API resources.
 *
 * Disables the default 'data' wrapper so resources return their fields directly.
 * ApiResponse already provides the { success, message, data, meta, errors } envelope.
 *
 * All subclasses must override toArray() — enforced by code review, not PHP abstract
 * (PHP does not allow re-declaring inherited non-abstract methods as abstract).
 */
class BaseApiResource extends JsonResource
{
    /**
     * Disable the default 'data' wrapper.
     */
    public static $wrap = null;
}
