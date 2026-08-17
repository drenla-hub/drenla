<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after `auth:client`. Re-checks portal_access_enabled on every request (not
 * just at login) so staff revoking a client's access takes effect immediately,
 * even mid-session.
 */
class EnsureClientPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user('client');

        if (! $client || ! $client->portal_access_enabled) {
            Auth::guard('client')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Portal access is not currently enabled for this account.');
        }

        return $next($request);
    }
}
