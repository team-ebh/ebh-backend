<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Settings\GetCustomerSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Customer\Settings\CustomerSettingsResource;

/**
 * @tags Settings
 */
class SettingsController extends Controller
{
    /**
     * Get customer settings
     *
     * Returns customer-related settings and configurations
     *
     * @unauthenticated
     */
    public function index(GetCustomerSettingsAction $action): CustomerSettingsResource
    {
        return new CustomerSettingsResource($action());
    }
}
