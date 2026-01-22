<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\StaticPage\StaticPageListAction;
use App\Enums\StaticPage\ApplicationTypeEnum;
use App\Exceptions\InvalidRequestException;
use App\Http\Resources\Api\V1\Shared\StaticPage\StaticPageDetailsResource;
use App\Http\Resources\Api\V1\Shared\StaticPage\StaticPagesResource;
use Nizek\StaticPage\Database\Models\StaticPage;

/**
 * @tags Static Pages
 */
class StaticPageController
{
    /**
     * Get static page list
     *
     * @unauthenticated
     */
    public function index(StaticPageListAction $action): StaticPagesResource
    {
        return new StaticPagesResource($action());
    }

    /**
     * Get static page detail
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    public function show(StaticPage $staticPage): StaticPageDetailsResource
    {
        throw_unless(
            $staticPage->enabled() && $staticPage->application_type === ApplicationTypeEnum::RIDER->value,
            InvalidRequestException::class
        );

        return new StaticPageDetailsResource($staticPage);
    }
}
