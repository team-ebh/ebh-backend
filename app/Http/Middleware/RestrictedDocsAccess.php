<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RestrictedDocsAccess
{
    public function handle($request, \Closure $next)
    {
        //        if (app()->environment('local')) {
        //            return $next($request);
        //        }

        if (Gate::allows('viewApiDocs')) {
            return $next($request);
        }

        if (! Auth::check()) {
            return redirect()->guest('/login');
        }

        abort(403);
    }
}
