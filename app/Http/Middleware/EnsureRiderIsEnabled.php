<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Rider\RiderAccountDisabledException;
use App\Models\Rider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRiderIsEnabled
{
    /**
     * Handle an incoming request.
     *
     * @throws RiderAccountDisabledException
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Rider|null $rider */
        $rider = $request->user('rider');

        if ($rider && ! $rider->isEnabled()) {
            // Revoke all tokens to log out the rider
            $rider->tokens()->delete();

            throw new RiderAccountDisabledException();
        }

        return $next($request);
    }
}
