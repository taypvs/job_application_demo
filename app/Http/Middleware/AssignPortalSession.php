<?php

namespace App\Http\Middleware;

use App\Support\Portal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignPortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Portal::isSeparated()) {
            return $next($request);
        }

        config([
            'session.cookie' => Portal::sessionCookie(),
            'auth.defaults.guard' => Portal::guard(),
        ]);

        if (app()->resolved('session')) {
            app('session')->forgetDrivers();
        }

        return $next($request);
    }
}
