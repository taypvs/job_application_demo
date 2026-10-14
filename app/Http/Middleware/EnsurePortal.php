<?php

namespace App\Http\Middleware;

use App\Support\Portal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortal
{
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        if (! Portal::isSeparated() || Portal::current() === $portal) {
            return $next($request);
        }

        return redirect()->away(Portal::baseUrl($portal).$request->getRequestUri());
    }
}
