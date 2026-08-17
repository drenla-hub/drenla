<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Services\ProposalPdfExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProposalController extends Controller
{
    /**
     * List of the authenticated client's own client-visible proposals.
     */
    public function index(Request $request): View
    {
        $proposals = $request->user('client')->proposals()
            ->where('is_client_visible', true)
            ->latest('issue_date')
            ->get();

        return view('portal.proposals.index', compact('proposals'));
    }

    /**
     * Scoped strictly to the authenticated client's own visible proposals — a client
     * can never view another client's proposal, even by guessing an id.
     */
    public function show(Request $request, Proposal $proposal): View
    {
        $client = $request->user('client');

        abort_unless(
            $proposal->client_id === $client->id && $proposal->is_client_visible,
            404
        );

        $proposal->load('files');

        $files = $proposal->files->where('is_client_visible', true);

        // Same branded print/PDF layout admin sees in "Preview" (admin/proposals/print.blade.php),
        // not a flattened re-render of the document blocks — the signed print route is already
        // designed to be hit with no session (it's how headless Chrome renders it for export), so
        // it's safe to embed here via a short-lived signed URL scoped to this one proposal.
        $printUrl = URL::temporarySignedRoute(
            'admin.proposals.print',
            now()->addHour(),
            ['proposal' => $proposal->id]
        );

        return view('portal.proposals.show', compact('proposal', 'files', 'printUrl'));
    }

    /**
     * Client-facing PDF download — same rendered document as the print preview, generated
     * fresh via ProposalPdfExporter (not tied to admin's last_exported_at bookkeeping, which
     * tracks staff-initiated sends/exports, not client self-service downloads).
     */
    public function download(Request $request, Proposal $proposal, ProposalPdfExporter $exporter): BinaryFileResponse
    {
        $client = $request->user('client');

        abort_unless(
            $proposal->client_id === $client->id && $proposal->is_client_visible,
            404
        );

        $result = $exporter->export($proposal->loadMissing('client'));

        return response()->download($result->path, $result->filename)->deleteFileAfterSend(true);
    }
}
