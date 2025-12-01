<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\GetAppStateAction;
use App\DTOs\Api\V1\Customer\AppStateDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Customer\AppStateResource;
use Illuminate\Http\Request;

/**
 * @tags Customer
 */
class CustomerController extends Controller
{
    /**
     * Get current app state
     *
     * Returns the current state of the customer app to help mobile app determine its state
     *
     * @authenticated
     */
    public function appState(
        Request $request,
        AppStateDTO $dto,
        GetAppStateAction $action,
    ): AppStateResource {
        $dto->getDataFromRequest($request);

        return new AppStateResource($action($dto));
    }
}
