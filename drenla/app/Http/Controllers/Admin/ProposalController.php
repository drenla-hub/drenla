<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ParsesProposalBlockInput;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\Proposal;
use App\Services\DocumentDeliveryService;
use App\Services\ProposalDocumentValidator;
use App\Services\ProposalPdfExporter;
use App\Support\ProposalDocumentData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProposalController extends Controller
{
    use ParsesProposalBlockInput;

    public function index(): View
    {
        $proposals = Proposal::with('client')->latest()->get();

        return view('admin.proposals.index', compact('proposals'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();

        $proposal = new Proposal([
            'status' => 'draft',
            'document_status' => 'draft',
            'template_key' => ProposalDocumentData::TEMPLATE_KEY,
            'template_version' => ProposalDocumentData::TEMPLATE_VERSION,
            'issue_date' => now()->toDateString(),
        ]);

        return view('admin.proposals.form', $this->formViewData($proposal, $clients));
    }

    public function store(Request $request): RedirectResponse
    {
        $proposal = Proposal::create($this->validatedData($request) + [
            'created_by' => $request->user()->id,
        ]);

        $this->syncLinkedFinanceDocument($proposal, $request->input('finance_document_id'));

        return redirect()->route('admin.proposals.edit', $proposal)->with('status', 'Project brief workspace created.');
    }

    public function edit(Proposal $proposal): View
    {
        $clients = Client::orderBy('name')->get();

        return view('admin.proposals.form', $this->formViewData($proposal, $clients));
    }

    public function update(Request $request, Proposal $proposal): RedirectResponse
    {
        $proposal->update($this->validatedData($request, $proposal));
        $this->syncLinkedFinanceDocument($proposal, $request->input('finance_document_id'));

        return redirect()->route('admin.proposals.edit', $proposal)->with('status', 'Project brief updated.');
    }

    public function preview(Proposal $proposal): View
    {
        return view('admin.proposals.print', [
            'proposal' => $proposal->loadMissing('client'),
            'document' => $proposal->normalized_document_data,
            'quotation' => $proposal->quotation()->with('items', 'client')->first(),
            'previewMode' => true,
        ]);
    }

    /**
     * Render the print template over HTTP (no previewMode).
     * Accessed by the PDF exporter via a temporary signed URL — no auth required.
     */
    public function printPdf(Proposal $proposal): View
    {
        return view('admin.proposals.print', [
            'proposal' => $proposal->loadMissing('client'),
            'document' => $proposal->normalized_document_data,
            'quotation' => $proposal->quotation()->with('items', 'client')->first(),
            'previewMode' => false,
        ]);
    }

    public function export(Proposal $proposal, ProposalPdfExporter $exporter): BinaryFileResponse
    {
        $result = $exporter->export($proposal->loadMissing('client'));

        $proposal->forceFill([
            'last_exported_at' => now(),
            'last_exported_filename' => $result->filename,
        ])->save();

        return response()->download($result->path, $result->filename)->deleteFileAfterSend(true);
    }

    public function send(Proposal $proposal, DocumentDeliveryService $delivery): RedirectResponse
    {
        try {
            $delivery->sendProposal($proposal->loadMissing('client'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['send' => $exception->getMessage()]);
        }

        return back()->with('status', 'Project brief emailed to the client.');
    }

    /**
     * Return the preset scope + sections for a given template key as JSON.
     * Consumed by the proposal form when the user switches template type.
     */
    public function templateDefaults(Request $request): JsonResponse
    {
        $key = $request->query('key', ProposalDocumentData::TEMPLATE_KEY);
        $options = ProposalDocumentData::templateOptions();

        if (! array_key_exists($key, $options)) {
            return response()->json(['error' => 'Unknown template key.'], 422);
        }

        return response()->json(ProposalDocumentData::presetFor($key));
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $proposal->delete();

        return redirect()->route('admin.proposals.index')->with('status', 'Project brief deleted.');
    }

    private function validatedData(Request $request, ?Proposal $proposal = null): array
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:proposals,slug,'.($proposal?->id ?? 'null')],
            'status' => ['required', 'in:draft,sent,accepted,won,archived'],
            'template_key' => ['required', 'in:'.implode(',', array_keys(ProposalDocumentData::templateOptions()))],
            'document_status' => ['required', 'in:draft,review,approved,issued'],
            'reference_number' => ['nullable', 'string', 'max:255', 'unique:proposals,reference_number,'.($proposal?->id ?? 'null')],
            'issue_date' => ['nullable', 'date'],
            'finance_document_id' => ['nullable', 'exists:finance_documents,id'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'is_client_visible' => ['nullable', 'boolean'],
        ]);

        // The admin form authors structured blocks (bullet_list, multi_column_list,
        // stage_grid, signature_block) through line-based mini-syntax textareas —
        // see ParsesProposalBlockInput's docblock for why — so the raw request
        // needs converting into the real block shape before either validation or
        // ProposalDocumentData ever sees it. Parse once, use the same array for both.
        $documentInput = $this->parseProposalBlockInput($request->input('document', []));

        // Document-level validation (header/hero/intro/sections/acceptance, all block
        // types) is delegated to ProposalDocumentValidator — the single source of
        // truth for the block-type schema, so a malformed block of any type (wrong
        // shape, missing required field for that type) is rejected here rather than
        // silently reaching the renderer.
        ProposalDocumentValidator::validate($documentInput);

        if ($request->boolean('is_client_visible')) {
            $data['access_token'] = $proposal?->access_token ?: Str::random(32);
        }

        $document = ProposalDocumentData::fromRequest($documentInput, $proposal);
        $data['reference_number'] = $data['reference_number'] ?? $proposal?->reference_number;

        if ($data['client_id'] && blank($document['header']['client_name'])) {
            $client = Client::query()->find($data['client_id']);
            $document['header']['client_name'] = $client?->name ?? '';
        }

        $document['header']['client_name'] = $document['header']['client_name'] ?: ($proposal?->client?->name ?? '');
        [$data['summary'], $derivedBody] = ProposalDocumentData::narrativeSummary($document);
        $data['body'] = trim(implode("\n\n", array_filter([$data['body'] ?? null, $derivedBody])));
        $data['template_version'] = ProposalDocumentData::templateOptions()[$data['template_key']]['version'];
        $data['document_data'] = $document;
        $data['is_client_visible'] = $request->boolean('is_client_visible');

        return $data;
    }

    private function formViewData(Proposal $proposal, $clients): array
    {
        $linkedFinanceDocId = old(
            'finance_document_id',
            $proposal->financeDocuments()
                ->whereIn('type', ['quotation', 'invoice'])
                ->orderByRaw("CASE WHEN type = 'quotation' THEN 0 ELSE 1 END")
                ->orderByDesc('issue_date')
                ->value('id')
        );

        return [
            'proposal' => $proposal,
            'clients' => $clients,
            'financeDocuments' => $this->proposalFinanceOptions($proposal),
            'linkedFinanceDocumentId' => $linkedFinanceDocId,
            'templateOptions' => ProposalDocumentData::templateOptions(),
            'templateDefaultsUrl' => route('admin.proposals.template-defaults'),
            'document' => ProposalDocumentData::normalize($proposal->document_data, $proposal),
            'statusOptions' => [
                'draft' => 'Draft',
                'sent' => 'Sent',
                'accepted' => 'Accepted',
                'won' => 'Won',
                'archived' => 'Archived',
            ],
            'documentStatusOptions' => [
                'draft' => 'Drafting',
                'review' => 'In Review',
                'approved' => 'Approved',
                'issued' => 'Issued',
            ],
        ];
    }

    private function proposalFinanceOptions(Proposal $proposal)
    {
        $query = FinanceDocument::with(['client', 'proposal'])
            ->whereIn('type', ['quotation', 'invoice'])
            ->orderByRaw("CASE WHEN type = 'quotation' THEN 0 ELSE 1 END")
            ->orderByDesc('issue_date');

        if ($proposal->exists) {
            $query->where(function ($inner) use ($proposal) {
                $inner->whereNull('proposal_id')->orWhere('proposal_id', $proposal->id);
            });
        } else {
            $query->whereNull('proposal_id');
        }

        return $query->get();
    }

    private function syncLinkedFinanceDocument(Proposal $proposal, mixed $financeDocumentId): void
    {
        $proposal->financeDocuments()
            ->whereIn('type', ['quotation', 'invoice'])
            ->update(['proposal_id' => null]);

        if (! $financeDocumentId) {
            $proposal->forceFill(['value' => null])->save();

            return;
        }

        $document = FinanceDocument::query()->find($financeDocumentId);
        if (! $document || ! in_array($document->type, ['quotation', 'invoice'], true)) {
            $proposal->forceFill(['value' => null])->save();

            return;
        }

        $document->update([
            'proposal_id' => $proposal->id,
            'client_id' => $proposal->client_id ?: $document->client_id,
        ]);

        $proposal->forceFill([
            'value' => $document->total_amount,
        ])->save();
    }
}
