<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;
use Symfony\Component\HttpFoundation\Response;

class NoActiveTripException extends BaseException
{
    protected $code = Response::HTTP_NO_CONTENT;

    protected $message = 'trips.no_active_trip';
}
