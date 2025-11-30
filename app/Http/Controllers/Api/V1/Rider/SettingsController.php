<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\Settings\GetRiderSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rider\Settings\RiderSettingsResource;

/**
 * @tags Settings
 */
class SettingsController extends Controller
{
    /**
     * Get rider settings
     *
     * Returns rider-related settings and configurations
     *
     * @authenticated
     */
    public function index(GetRiderSettingsAction $action): RiderSettingsResource
    {
        return new RiderSettingsResource($action());
    }
}
