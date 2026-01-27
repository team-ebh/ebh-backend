<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\Trip\DownloadTripReceiptAction;
use App\Actions\Api\V1\Rider\Trip\GetTripReceiptLinkAction;
use App\Actions\Api\V1\Rider\Trip\History\GetFiltersAction;
use App\Actions\Api\V1\Rider\Trip\History\GetPastTripDetailsAction;
use App\Actions\Api\V1\Rider\Trip\History\GetPastTripsAction;
use App\DTOs\Api\V1\Rider\Trip\GetTripReceiptLinkDTO;
use App\DTOs\Api\V1\Rider\Trip\History\GetPastTripsDTO;
use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\Trip\History\GetPastTripsRequest;
use App\Http\Resources\Api\V1\Rider\Trip\History\FiltersResource;
use App\Http\Resources\Api\V1\Rider\Trip\History\HistoryPastTripListResource;
use App\Http\Resources\Api\V1\Rider\Trip\History\PastTripDetailsResource;
use App\Http\Resources\Api\V1\Rider\Trip\TripReceiptLinkResource;
use App\Models\Trip;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @tags Trip History
 */
class HistoryTripController extends Controller
{
    /**
     * Get trip history filters
     *
     * Returns available filter options with rider statistics:
     * - Filters (all, completed, canceled) with is_default flag
     * - Total completed rides count
     * - Total canceled trips count
     *
     * @authenticated
     */
    public function filters(GetFiltersAction $action): FiltersResource
    {
        return new FiltersResource($action(auth('rider')->id()));
    }

    /**
     * Get past trips list
     *
     * Returns completed and canceled trips with customer information.
     * Supports filtering by status (all, completed, canceled).
     *
     * @authenticated
     */
    public function index(
        GetPastTripsRequest $request,
        GetPastTripsDTO $dto,
        GetPastTripsAction $action
    ): HistoryPastTripListResource {
        $dto->getDataFromRequest($request);

        return new HistoryPastTripListResource($action($dto));
    }

    /**
     * Get past trip details
     *
     * Returns detailed information about a specific past trip including:
     * - Customer information
     * - Locations
     * - Price breakdown
     * - Ride type and passenger count
     * - Accessibility requirements
     * - Waiting time (if applicable)
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function show(int $trip, GetPastTripDetailsAction $action): PastTripDetailsResource
    {
        return new PastTripDetailsResource($action($trip, auth('rider')->id()));
    }

    /**
     * Get trip receipt link
     *
     * Generates a temporary signed URL to download the trip receipt.
     * The link is valid for 10 minutes and requires a valid signature.
     * Available only for completed trips.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function receiptLink(
        Trip $trip,
        Request $request,
        GetTripReceiptLinkDTO $dto,
        GetTripReceiptLinkAction $action
    ): TripReceiptLinkResource {
        $dto->getDataFromRequest($request, $trip);

        return new TripReceiptLinkResource($action($dto));
    }

    /**
     * Download trip receipt
     *
     * Downloads the trip receipt as a PDF file.
     * Requires a valid signed URL from receiptLink endpoint.
     * Available only for completed trips.
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    #[ExcludeRouteFromDocs]
    public function downloadReceipt(
        Trip $trip,
        Request $request,
        DownloadTripReceiptAction $action
    ): Response {
        throw_unless($request->hasValidSignature(), InvalidRequestException::class);

        return $action($trip);
    }
}
