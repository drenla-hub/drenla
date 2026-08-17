<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FinanceDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $documents = $request->user('client')
            ->financeDocuments()
            ->latest('issue_date')
            ->get();

        return view('portal.finance.index', compact('documents'));
    }

    /** Scoped strictly to the authenticated client's own finance documents. */
    public function show(Request $request, FinanceDocument $financeDocument): View
    {
        $client = $request->user('client');

        abort_unless($financeDocument->client_id === $client->id, 404);

        $financeDocument->load('items');

        return view('portal.finance.show', ['document' => $financeDocument]);
    }
}
