<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user() || !$request->user()->is_active) {
            abort(403, 'Unauthorized access.');
        }

        if (in_array($request->user()->role, $roles)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}