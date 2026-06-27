<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * Base controller for all API controllers.
 *
 * All API module controllers must extend this class.
 *
 * This class is intentionally minimal. It does NOT include ApiResponseTrait
 * because controllers should delegate response building to ApiResponse directly.
 * Keeping this thin enforces the pattern: Controller → ApiResponse::success/error().
 */
abstract class BaseApiController extends Controller
{
    // Intentionally empty.
    // Future: add shared middleware registration or auth helpers here if needed.
}
