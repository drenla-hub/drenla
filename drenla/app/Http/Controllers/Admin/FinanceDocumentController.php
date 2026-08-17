<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\FinanceDocumentTransaction;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Proposal;
use App\Services\DocumentDeliveryService;
use App\Services\FinanceDocumentPdfExporter;
use App\Services\MilestoneGatingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceDocumentController extends Controller
{
    public function __construct(private readonly MilestoneGatingService $gating) {}

    public function index(): View
    {
        $documents = FinanceDocument::with(['client', 'proposal'])
            ->latest()
            ->get();

        return view('admin.finance.index', compact('documents'));
    }

    public function create(Request $request): View
    {
        $clients = Client::orderBy('name')->get();
        $projects = Project::with('client')->orderBy('created_at', 'desc')->get();
        $proposals = Proposal::with('client')->orderBy('created_at', 'desc')->get();
        $document = new FinanceDocument([
            'type' => $request->query('type', 'quotation'),
            'status' => 'draft',
            'currency' => 'KES',
            'issue_date' => now()->toDateString(),
        ]);

        // Pre-link to a proposal if passed as query param
        if ($proposalId = $request->query('proposal_id')) {
            $proposal = Proposal::with('client')->find($proposalId);
            if ($proposal) {
                $document->proposal_id = $proposal->id;
                $document->client_id = $proposal->client_id;
            }
        }

        if ($projectId = $request->query('project_id')) {
            $project = Project::with(['client', 'proposal'])->find($projectId);
            if ($project) {
                $document->project_id = $project->id;
                $document->client_id = $project->client_id;
                $document->proposal_id = $project->proposal_id;
            }
        }

        return view('admin.finance.form', [
            'document' => $document,
            'clients' => $clients,
            'projects' => $projects,
            'proposals' => $proposals,
            'items' => [],
            'transactions' => [],
            'typeOptions' => static::typeOptions(),
            'statusOptions' => static::statusOptions(),
            'currencyOptions' => static::currencyOptions(),
            'blockedMilestones' => $this->blockedMilestonesFor($document->project),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $items = $this->validatedItems($request);
        $transactions = $this->validatedTransactions($request);

        $doc = FinanceDocument::create($data);

        foreach ($items as $item) {
            $doc->items()->create($item);
        }

        foreach ($transactions as $index => $transaction) {
            $doc->transactions()->create([...$transaction, 'sort_order' => $index]);
        }

        $doc->recalculate()->save();

        if ($doc->proposal_id && in_array($doc->type, ['quotation', 'invoice'], true)) {
            $doc->proposal?->forceFill(['value' => $doc->total_amount])->save();
        }

        return redirect()
            ->route('admin.finance.edit', $doc)
            ->with('status', ucfirst($doc->type).' created.');
    }

    public function edit(FinanceDocument $finance): View
    {
        $clients = Client::orderBy('name')->get();
        $projects = Project::with('client')->orderBy('created_at', 'desc')->get();
        $proposals = Proposal::with('client')->orderBy('created_at', 'desc')->get();

        return view('admin.finance.form', [
            'document' => $finance->loadMissing(['client', 'proposal', 'project', 'items', 'transactions']),
            'clients' => $clients,
            'projects' => $projects,
            'proposals' => $proposals,
            'items' => $finance->items,
            'transactions' => $finance->transactions,
            'typeOptions' => static::typeOptions(),
            'statusOptions' => static::statusOptions(),
            'currencyOptions' => static::currencyOptions(),
            'blockedMilestones' => $this->blockedMilestonesFor($finance->project),
        ]);
    }

    public function update(Request $request, FinanceDocument $finance): RedirectResponse
    {
        $data = $this->validatedData($request);
        $items = $this->validatedItems($request);
        $transactions = $this->validatedTransactions($request);

        $finance->update($data);
        $finance->items()->delete();
        $finance->transactions()->delete();

        foreach ($items as $item) {
            $finance->items()->create($item);
        }

        foreach ($transactions as $index => $transaction) {
            $finance->transactions()->create([...$transaction, 'sort_order' => $index]);
        }

        $finance->recalculate()->save();

        if ($finance->proposal_id && in_array($finance->type, ['quotation', 'invoice'], true)) {
            $finance->proposal?->forceFill(['value' => $finance->total_amount])->save();
        }

        return redirect()
            ->route('admin.finance.edit', $finance)
            ->with('status', ucfirst($finance->type).' updated.');
    }

    public function destroy(FinanceDocument $finance): RedirectResponse
    {
        $finance->delete();

        return redirect()
            ->route('admin.finance.index')
            ->with('status', 'Document deleted.');
    }

    public function preview(FinanceDocument $finance): View
    {
        return view('admin.finance.print', [
            'document' => $finance->loadMissing(['client', 'items', 'transactions']),
            'previewMode' => true,
        ]);
    }

    /**
     * Render the print template over HTTP (no previewMode).
     * Accessed by the PDF exporter via a temporary signed URL — no auth required.
     */
    public function printPdf(FinanceDocument $finance): View
    {
        return view('admin.finance.print', [
            'document' => $finance->loadMissing(['client', 'items', 'transactions']),
            'previewMode' => false,
        ]);
    }

    public function export(FinanceDocument $finance, FinanceDocumentPdfExporter $exporter): BinaryFileResponse
    {
        $result = $exporter->export($finance->loadMissing(['client', 'items', 'transactions']));

        return response()->download($result->path, $result->filename)->deleteFileAfterSend(true);
    }

    public function send(FinanceDocument $finance, DocumentDeliveryService $delivery): RedirectResponse
    {
        try {
            $delivery->sendFinanceDocument($finance->loadMissing(['client', 'items', 'transactions']));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['send' => $exception->getMessage()]);
        }

        return back()->with('status', ucfirst($finance->type).' emailed to the client.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Finance documents aren't linked to a specific milestone in the schema, only to
     * a project — so "gating status" here means: does the linked project currently
     * have a payment-gated milestone blocking its progress? Surfaced so staff editing
     * an invoice can see why a client's next stage might still be locked.
     *
     * @return Collection<int, array{milestone: ProjectMilestone, reason: ?string}>
     */
    private function blockedMilestonesFor(?Project $project)
    {
        if (! $project) {
            return collect();
        }

        return $project->milestones()->get()
            ->filter(fn ($milestone) => $this->gating->isBlocked($milestone))
            ->map(fn ($milestone) => [
                'milestone' => $milestone,
                'reason' => $this->gating->blockingReason($milestone),
            ])
            ->values();
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'proposal_id' => ['nullable', 'exists:proposals,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'type' => ['required', 'in:quotation,invoice,receipt,statement'],
            'status' => ['required', 'in:draft,sent,accepted,paid,cancelled'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:3'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string'],
            'payment_info' => ['nullable', 'string'],
            'next_due_label' => ['nullable', 'string', 'max:255'],
            'next_due_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    /** @return array<int, array{transaction_date: string, label: string, type: string, amount: float}> */
    private function validatedTransactions(Request $request): array
    {
        $request->validate([
            'transactions' => ['nullable', 'array'],
            'transactions.*.transaction_date' => ['required_with:transactions.*.label', 'nullable', 'date'],
            'transactions.*.label' => ['nullable', 'string', 'max:255'],
            'transactions.*.type' => ['nullable', 'in:invoice,payment,credit'],
            'transactions.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        return collect($request->input('transactions', []))
            ->filter(fn ($t) => filled($t['label'] ?? ''))
            ->map(fn ($t) => [
                'transaction_date' => $t['transaction_date'],
                'label' => $t['label'],
                'type' => $t['type'] ?? FinanceDocumentTransaction::TYPE_PAYMENT,
                'amount' => (float) ($t['amount'] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function validatedItems(Request $request): array
    {
        $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        return collect($request->input('items', []))
            ->filter(fn ($item) => filled($item['title'] ?? ''))
            ->map(function ($item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);

                return [
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total' => round($qty * $price, 2),
                ];
            })
            ->values()
            ->all();
    }

    public static function typeOptions(): array
    {
        return [
            'quotation' => 'Quotation',
            'invoice' => 'Invoice',
            'receipt' => 'Receipt',
            'statement' => 'Statement',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function currencyOptions(): array
    {
        return ['KES' => 'KES', 'USD' => 'USD', 'GBP' => 'GBP', 'EUR' => 'EUR'];
    }
}
