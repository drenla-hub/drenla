<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parameterized module-permission gate: `middleware('permission:manage_clients')`
 * or, for "manage implies view" read routes, `middleware('permission:view_clients,manage_clients')`
 * — passes if the user has ANY of the given keys. See App\Support\Permission.
 */
class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! $request->user()?->hasAnyPermission(...$permissions)) {
            abort(403);
        }

        return $next($request);
    }
}
