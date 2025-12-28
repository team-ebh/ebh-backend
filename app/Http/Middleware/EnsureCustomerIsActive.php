<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Customer\CustomerAccountDisabledException;
use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsActive
{
    /**
     * Handle an incoming request.
     *
     * @throws CustomerAccountDisabledException
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Customer|null $customer */
        $customer = $request->user('customer');

        if ($customer && $customer->isDisabled()) {
            // Revoke all tokens to log out the customer
            $customer->tokens()->delete();

            throw new CustomerAccountDisabledException();
        }

        return $next($request);
    }
}
