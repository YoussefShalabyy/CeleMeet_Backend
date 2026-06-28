<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller for all API controllers.
 *
 * All API module controllers must extend this class.
 * Includes AuthorizesRequests so controllers can call $this->authorize()
 * backed by the policies registered in AppServiceProvider.
 */
abstract class BaseApiController extends Controller
{
    use AuthorizesRequests;
}
