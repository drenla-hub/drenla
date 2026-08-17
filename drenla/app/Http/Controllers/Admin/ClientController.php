<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::orderBy('name')->get();

        return view('admin.clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('admin.clients.form', ['client' => new Client]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Client::create($this->validatedData($request));

        return redirect()->route('admin.clients.edit', $client)->with('status', 'Client created.');
    }

    public function edit(Client $client): View
    {
        $client->load(['contacts', 'leadNotes.author']);

        return view('admin.clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validatedData($request, $client));

        return redirect()->route('admin.clients.edit', $client)->with('status', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('admin.clients.index')->with('status', 'Client deleted.');
    }

    public function regeneratePortalToken(Client $client): RedirectResponse
    {
        abort_unless($client->portal_access_enabled, 422, 'Enable portal access before issuing a link.');

        $client->regeneratePortalToken();

        return redirect()->route('admin.clients.edit', $client)->with('status', 'Portal link regenerated — the previous link no longer works.');
    }

    public function storeContact(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ]) + ['is_primary' => $request->boolean('is_primary')];

        $client->contacts()->create($data);

        return redirect()->route('admin.clients.edit', $client)->with('status', 'Contact added.');
    }

    public function destroyContact(Client $client, ClientContact $contact): RedirectResponse
    {
        abort_unless($contact->client_id === $client->id, 404);

        $contact->delete();

        return redirect()->route('admin.clients.edit', $client)->with('status', 'Contact removed.');
    }

    public function storeLeadNote(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $client->leadNotes()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return redirect()->route('admin.clients.edit', $client)->with('status', 'Note added.');
    }

    private function validatedData(Request $request, ?Client $client = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:clients,email,'.($client?->id ?? 'null')],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:lead,active,inactive'],
            'notes' => ['nullable', 'string'],
            'portal_access_enabled' => ['nullable', 'boolean'],
        ]);

        $portalEnabled = $request->boolean('portal_access_enabled');

        $data['portal_access_enabled'] = $portalEnabled;
        $data['portal_access_token'] = $portalEnabled
            ? ($client?->portal_access_token ?: Str::random(40))
            : null;

        return $data;
    }
}
