<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientPortalAuthController extends Controller
{
    /** Manual-entry fallback login form (paste the access token). */
    public function create(): View
    {
        return view('auth.portal-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        return $this->attemptLogin($data['token'])
            ?? throw ValidationException::withMessages([
                'token' => 'That access link is invalid or has been disabled.',
            ]);
    }

    /**
     * Magic-link entry point — the URL staff copy from the client's admin screen, or
     * that DocumentDeliveryService emails out. Accepts an optional `?redirect=` to
     * land the client straight on a specific document (e.g. the proposal they were
     * just sent) instead of the generic dashboard — see safeRedirectPath() for the
     * open-redirect guard.
     */
    public function access(Request $request, string $token): RedirectResponse
    {
        return $this->attemptLogin($token, $this->safeRedirectPath($request->query('redirect')))
            ?? redirect()->route('portal.login')->withErrors([
                'token' => 'That access link is invalid or has been disabled.',
            ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    private function attemptLogin(string $token, ?string $redirectTo = null): ?RedirectResponse
    {
        $client = Client::query()
            ->where('portal_access_token', $token)
            ->where('portal_access_enabled', true)
            ->first();

        if (! $client) {
            return null;
        }

        Auth::guard('client')->login($client);
        request()->session()->regenerate();

        return redirect()->to($redirectTo ?? route('portal.dashboard'));
    }

    /** Only ever redirect to a same-app `/portal/...` path — never an external/absolute URL. */
    private function safeRedirectPath(?string $path): ?string
    {
        if (! $path || ! str_starts_with($path, '/portal/') || str_contains($path, '://')) {
            return null;
        }

        return $path;
    }
}
