@extends('layouts.admin')

@php
    $isStatement = old('type', $document->type) === 'statement';
@endphp

@section('content')
<style>
    textarea.auto-resize { resize: none; overflow: hidden; min-height: 4rem; }
    @keyframes rowEnter { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
    .item-row-enter { animation: rowEnter 0.2s ease forwards; }
</style>

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div class="max-w-3xl">
            <p class="text-eyebrow">Financial Documents</p>
            <h2 class="mt-3 text-headline">
                {{ $document->exists ? ucfirst($document->type).' workspace' : 'New financial document' }}
            </h2>
            @if ($document->exists && $document->proposal)
                <p class="mt-3 text-sm text-white/50">
                    Linked to project brief
                    <a href="{{ route('admin.proposals.edit', $document->proposal) }}" class="underline hover:text-white transition">{{ $document->proposal->reference_number ?: $document->proposal->title }}</a>
                    — this document will be embedded in the exported project brief PDF.
                </p>
            @elseif ($document->exists)
                <p class="mt-3 text-sm text-white/40">Not linked to a project brief. Link it below to embed in project brief exports.</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.finance.index') }}" class="inline-flex items-center border border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:border-white/25 hover:text-white">
                ← Back
            </a>
            @if ($document->exists)
                <a href="{{ route('admin.finance.preview', $document) }}" target="_blank" class="inline-flex items-center border border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:border-white/25 hover:text-white">Preview ↗</a>
                <form method="POST" action="{{ route('admin.finance.send', $document) }}" class="inline" onsubmit="return confirm('Email this {{ $document->type }} to {{ $document->client?->email ?? 'the client' }}?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center border border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:border-white/25 hover:text-white">Send to client ✉</button>
                </form>
                <a href="{{ route('admin.finance.export', $document) }}" class="inline-flex items-center border border-white bg-white px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-black transition hover:bg-white/90">Export PDF ↓</a>
            @endif
        </div>
    </div>

    @if (($blockedMilestones ?? collect())->isNotEmpty())
        <div class="border border-red-900/50 bg-red-950/20 p-5 space-y-3">
            <p class="text-[9px] font-black uppercase tracking-[0.3em]" style="color:#c44">⛔ Linked project has payment-gated milestones</p>
            <ul class="space-y-2">
                @foreach ($blockedMilestones as $entry)
                    <li class="text-[12px] text-white/70">
                        <span class="font-medium text-white">{{ $entry['milestone']->title }}</span>
                        — {{ $entry['reason'] }}
                    </li>
                @endforeach
            </ul>
            <p class="text-[11px] text-white/40">Downstream project progress stays blocked until these are marked paid.</p>
        </div>
    @endif

    <form id="finance-form" method="POST"
        action="{{ $document->exists ? route('admin.finance.update', $document) : route('admin.finance.store') }}"
        class="space-y-8">
        @csrf
        @if ($document->exists) @method('PUT') @endif

        <div class="grid gap-8 2xl:grid-cols-[1.2fr_0.8fr]">

            {{-- Left --}}
            <div class="space-y-8">

                {{-- Identity --}}
                <section class="border border-white/10 bg-black/90 p-6">
                    <p class="text-eyebrow">Document identity</p>
                    <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Header fields</h3>

                    <div class="mt-8 grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Document type</label>
                            <select name="type" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                @foreach ($typeOptions as $val => $lbl)
                                    <option value="{{ $val }}" @selected(old('type', $document->type) === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Status</label>
                            <select name="status" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                @foreach ($statusOptions as $val => $lbl)
                                    <option value="{{ $val }}" @selected(old('status', $document->status ?: 'draft') === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Client</label>
                            <select name="client_id" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                <option value="">Select client</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected((string) old('client_id', $document->client_id) === (string) $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Linked project</label>
                            <select name="project_id" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                <option value="">None</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected((string) old('project_id', $document->project_id) === (string) $project->id)>
                                        {{ $project->title }} — {{ $project->client?->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-white/35">Use this to power commercial values and invoice tracking on the linked project.</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Linked project brief</label>
                            <select name="proposal_id" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                <option value="">None</option>
                                @foreach ($proposals as $proposal)
                                    <option value="{{ $proposal->id }}" @selected((string) old('proposal_id', $document->proposal_id) === (string) $proposal->id)>
                                        {{ $proposal->reference_number ?: $proposal->title }} — {{ $proposal->client?->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-white/35">When linked, this document is embedded as a page in the project brief PDF export.</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Reference number</label>
                            <input type="text" name="reference_number"
                                value="{{ old('reference_number', $document->reference_number) }}"
                                placeholder="e.g. DNR-SEP-002"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Currency</label>
                            <select name="currency" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                @foreach ($currencyOptions as $val => $lbl)
                                    <option value="{{ $val }}" @selected(old('currency', $document->currency ?: 'KES') === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Issue date</label>
                            <input type="date" name="issue_date"
                                value="{{ old('issue_date', optional($document->issue_date)->toDateString()) }}"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Due date</label>
                            <input type="date" name="due_date"
                                value="{{ old('due_date', optional($document->due_date)->toDateString()) }}"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                        </div>
                    </div>
                </section>

                {{-- Line items (quotation/invoice/receipt — not shown for statements) --}}
                <section class="border border-white/10 bg-black/90 p-6" data-doc-type-group="priced" style="{{ $isStatement ? 'display:none' : '' }}">
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-eyebrow">Line items</p>
                            <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Services & deliverables</h3>
                        </div>
                        <button type="button" id="add-item-btn"
                            class="inline-flex items-center gap-2 border border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:border-white/25 hover:text-white">
                            + Add item
                        </button>
                    </div>

                    {{-- Column headers --}}
                    <div class="mt-6 grid grid-cols-[1fr_90px_110px_80px_32px] gap-2 border-b border-white/10 pb-2">
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/25">Item / description</span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/25 text-right">Qty</span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/25 text-right">Unit price</span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/25 text-right">Total</span>
                        <span></span>
                    </div>

                    <div id="items-root" class="mt-3 space-y-2">
                        @foreach ($items as $i => $item)
                            <div class="item-row grid grid-cols-[1fr_90px_110px_80px_32px] gap-2 items-start" data-item-row>
                                <div class="space-y-1">
                                    <input type="text" name="items[{{ $i }}][title]"
                                        value="{{ old("items.$i.title", $item->title) }}"
                                        placeholder="Item title"
                                        class="w-full border border-white/12 bg-black px-3 py-2 text-sm text-white outline-none transition focus:border-white/40">
                                    <input type="text" name="items[{{ $i }}][description]"
                                        value="{{ old("items.$i.description", $item->description) }}"
                                        placeholder="Short description (optional)"
                                        class="w-full border border-white/12 bg-black px-3 py-2 text-xs text-white/50 outline-none transition focus:border-white/40">
                                </div>
                                <input type="number" step="0.01" name="items[{{ $i }}][quantity]"
                                    value="{{ old("items.$i.quantity", $item->quantity) }}"
                                    class="item-qty w-full border border-white/12 bg-black px-3 py-2 text-right text-sm text-white outline-none transition focus:border-white/40">
                                <input type="number" step="0.01" name="items[{{ $i }}][unit_price]"
                                    value="{{ old("items.$i.unit_price", $item->unit_price) }}"
                                    class="item-price w-full border border-white/12 bg-black px-3 py-2 text-right text-sm text-white outline-none transition focus:border-white/40">
                                <div class="item-total border border-white/8 bg-white/[0.02] px-3 py-2 text-right text-sm text-white/60">
                                    {{ number_format((float) $item->total, 0) }}
                                </div>
                                <button type="button" data-remove-item
                                    class="mt-2 text-white/25 hover:text-red-400 transition text-lg leading-none">×</button>
                            </div>
                        @endforeach
                    </div>

                    <template id="item-template">
                        <div class="item-row grid grid-cols-[1fr_90px_110px_80px_32px] gap-2 items-start item-row-enter" data-item-row>
                            <div class="space-y-1">
                                <input type="text" data-field="title" placeholder="Item title"
                                    class="w-full border border-white/12 bg-black px-3 py-2 text-sm text-white outline-none transition focus:border-white/40">
                                <input type="text" data-field="description" placeholder="Short description (optional)"
                                    class="w-full border border-white/12 bg-black px-3 py-2 text-xs text-white/50 outline-none transition focus:border-white/40">
                            </div>
                            <input type="number" step="0.01" value="1" data-field="quantity"
                                class="item-qty w-full border border-white/12 bg-black px-3 py-2 text-right text-sm text-white outline-none transition focus:border-white/40">
                            <input type="number" step="0.01" value="0" data-field="unit_price"
                                class="item-price w-full border border-white/12 bg-black px-3 py-2 text-right text-sm text-white outline-none transition focus:border-white/40">
                            <div class="item-total border border-white/8 bg-white/[0.02] px-3 py-2 text-right text-sm text-white/60">0</div>
                            <button type="button" data-remove-item class="mt-2 text-white/25 hover:text-red-400 transition text-lg leading-none">×</button>
                        </div>
                    </template>

                    {{-- Totals --}}
                    <div class="mt-6 space-y-2 border-t border-white/10 pt-5">
                        <div class="flex justify-between text-sm text-white/60">
                            <span class="uppercase tracking-[0.12em]">Sub total</span>
                            <span id="display-subtotal">—</span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-white/60">
                            <span class="uppercase tracking-[0.12em]">VAT / Tax</span>
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.01" name="tax_amount" id="tax-amount"
                                    value="{{ old('tax_amount', $document->tax_amount ?? 0) }}"
                                    class="w-28 border border-white/12 bg-black px-3 py-1.5 text-right text-sm text-white outline-none transition focus:border-white/40">
                            </div>
                        </div>
                        <div class="flex justify-between border-t border-white/10 pt-3 text-base font-semibold text-white">
                            <span class="uppercase tracking-[0.12em]">Total</span>
                            <span id="display-total">—</span>
                        </div>
                        <p class="text-xs text-white/30">Tax is entered as a fixed amount. Set 0 for VAT exclusive.</p>
                    </div>
                </section>

                {{-- Transaction ledger (statement only) --}}
                <section class="border border-white/10 bg-black/90 p-6" data-doc-type-group="statement" style="{{ $isStatement ? '' : 'display:none' }}">
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-eyebrow">Payment history</p>
                            <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Transaction ledger</h3>
                            <p class="mt-2 text-xs text-white/30">One row per invoice/payment/credit. Balance and aging (Current / 1-30 / 31-60 / 61-90+) are computed automatically from these rows, oldest first.</p>
                        </div>
                        <button type="button" id="add-transaction-btn"
                            class="inline-flex items-center gap-2 border border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:border-white/25 hover:text-white">
                            + Add transaction
                        </button>
                    </div>

                    <div class="mt-6 grid grid-cols-[130px_1fr_110px_120px_32px] gap-2 border-b border-white/10 pb-2">
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/25">Date</span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/25">Label</span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/25">Type</span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/25 text-right">Amount</span>
                        <span></span>
                    </div>

                    <div id="transactions-root" class="mt-3 space-y-2">
                        @foreach ($transactions as $i => $transaction)
                            <div class="item-row grid grid-cols-[130px_1fr_110px_120px_32px] gap-2 items-start" data-transaction-row>
                                <input type="date" name="transactions[{{ $i }}][transaction_date]"
                                    value="{{ old("transactions.$i.transaction_date", optional($transaction->transaction_date)->toDateString()) }}"
                                    class="w-full border border-white/12 bg-black px-2 py-2 text-xs text-white outline-none transition focus:border-white/40">
                                <input type="text" name="transactions[{{ $i }}][label]"
                                    value="{{ old("transactions.$i.label", $transaction->label) }}" placeholder="e.g. INV #DNR-SEP-2025"
                                    class="w-full border border-white/12 bg-black px-3 py-2 text-sm text-white outline-none transition focus:border-white/40">
                                <select name="transactions[{{ $i }}][type]"
                                    class="w-full border border-white/12 bg-black px-2 py-2 text-xs text-white outline-none transition focus:border-white/40">
                                    @foreach (['invoice' => 'Invoice', 'payment' => 'Payment', 'credit' => 'Credit'] as $val => $lbl)
                                        <option value="{{ $val }}" @selected(old("transactions.$i.type", $transaction->type) === $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.01" name="transactions[{{ $i }}][amount]"
                                    value="{{ old("transactions.$i.amount", $transaction->amount) }}"
                                    class="w-full border border-white/12 bg-black px-3 py-2 text-right text-sm text-white outline-none transition focus:border-white/40">
                                <button type="button" data-remove-transaction
                                    class="mt-2 text-white/25 hover:text-red-400 transition text-lg leading-none">×</button>
                            </div>
                        @endforeach
                    </div>

                    <template id="transaction-template">
                        <div class="item-row grid grid-cols-[130px_1fr_110px_120px_32px] gap-2 items-start item-row-enter" data-transaction-row>
                            <input type="date" data-field="transaction_date"
                                class="w-full border border-white/12 bg-black px-2 py-2 text-xs text-white outline-none transition focus:border-white/40">
                            <input type="text" data-field="label" placeholder="e.g. INV #DNR-SEP-2025"
                                class="w-full border border-white/12 bg-black px-3 py-2 text-sm text-white outline-none transition focus:border-white/40">
                            <select data-field="type"
                                class="w-full border border-white/12 bg-black px-2 py-2 text-xs text-white outline-none transition focus:border-white/40">
                                <option value="invoice">Invoice</option>
                                <option value="payment" selected>Payment</option>
                                <option value="credit">Credit</option>
                            </select>
                            <input type="number" step="0.01" value="0" data-field="amount"
                                class="w-full border border-white/12 bg-black px-3 py-2 text-right text-sm text-white outline-none transition focus:border-white/40">
                            <button type="button" data-remove-transaction class="mt-2 text-white/25 hover:text-red-400 transition text-lg leading-none">×</button>
                        </div>
                    </template>

                    <div class="mt-6 grid gap-4 border-t border-white/10 pt-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Next due label</label>
                            <input type="text" name="next_due_label" value="{{ old('next_due_label', $document->next_due_label) }}"
                                placeholder="e.g. PHASE 3 - INV #DNR-SEP-2025 - DUE PAYMENT"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-sm text-white outline-none transition focus:border-white/40">
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Next due amount</label>
                            <input type="number" step="0.01" name="next_due_amount" value="{{ old('next_due_amount', $document->next_due_amount) }}"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-sm text-white outline-none transition focus:border-white/40">
                        </div>
                    </div>
                </section>

                {{-- Notes + Payment --}}
                <section class="border border-white/10 bg-black/90 p-6">
                    <p class="text-eyebrow">Terms & notes</p>
                    <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Payment & admin notes</h3>

                    <div class="mt-8 space-y-6">
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Payment terms</label>
                            <textarea name="payment_terms" rows="4"
                                class="auto-resize w-full border border-white/12 bg-black px-4 py-3 text-sm text-white outline-none transition focus:border-white/40"
                                placeholder="e.g. 50% before project commencement&#10;30% after Stage 2&#10;20% after final deliverables">{{ old('payment_terms', $document->payment_terms) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Payment info (bank / Mpesa)</label>
                            <textarea name="payment_info" rows="5"
                                class="auto-resize w-full border border-white/12 bg-black px-4 py-3 text-sm text-white outline-none transition focus:border-white/40"
                                placeholder="A.C Name: DRENLA VENTURES LIMITED&#10;A.C No: 1324679069&#10;Currency: KES&#10;Swift Code: KCBLKENX&#10;Branch: KCB Karen Platinum Waterfront Centre">{{ old('payment_info', $document->payment_info) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Internal notes</label>
                            <textarea name="notes" rows="3"
                                class="auto-resize w-full border border-white/12 bg-black px-4 py-3 text-sm text-white outline-none transition focus:border-white/40"
                                placeholder="Private admin notes (not shown on PDF)">{{ old('notes', $document->notes) }}</textarea>
                        </div>
                    </div>
                </section>

            </div>{{-- /left --}}

            {{-- Sidebar --}}
            <aside class="space-y-8">
                <div class="sticky top-6 border border-white/10 bg-white px-5 py-5 text-black">
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-black/55">Actions</p>
                    <div class="mt-4 flex flex-col gap-3">
                        <button class="inline-flex items-center justify-center border border-black bg-black px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-black/90">
                            Save document
                        </button>
                        @if ($document->exists)
                            <form method="POST" action="{{ route('admin.finance.destroy', $document) }}" onsubmit="return confirm('Delete this document?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full inline-flex items-center justify-center border border-red-200 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-red-500 transition hover:border-red-400">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>

                    @if ($document->exists)
                        <div class="mt-5 border-t border-black/10 pt-4 space-y-2 text-[11px] text-black/40">
                            <div>Type: <span class="text-black/70 font-semibold">{{ ucfirst($document->type) }}</span></div>
                            <div>Status: <span class="text-black/70 font-semibold">{{ ucfirst($document->status) }}</span></div>
                            @if ($document->updated_at)
                                <div>Saved {{ $document->updated_at->diffForHumans() }}</div>
                            @endif
                        </div>
                    @endif
                </div>

                <section class="border border-white/10 bg-black/90 p-6">
                    <p class="text-eyebrow">PDF injection</p>
                    <h3 class="mt-3 text-xl font-light tracking-[-0.03em] text-white">Proposal PDF workflow</h3>
                    <div class="mt-5 space-y-3 text-sm leading-7 text-white/50">
                        <p>When this document is linked to a proposal, it is automatically embedded as the second-to-last page of the exported proposal PDF — before the acceptance form.</p>
                        <p>The final downloaded PDF structure will be:<br>
                            <span class="text-white/30 font-mono text-xs">Cover → Brief → Sections → Quotation → Acceptance</span>
                        </p>
                        <p>Only <strong class="text-white/70">Quotation</strong>-type documents are injected into proposals. Invoices and receipts are standalone.</p>
                    </div>
                </section>
            </aside>

        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const itemsRoot   = document.getElementById('items-root');
    const addBtn      = document.getElementById('add-item-btn');
    const itemTmpl    = document.getElementById('item-template');
    const taxInput    = document.getElementById('tax-amount');
    const dispSub     = document.getElementById('display-subtotal');
    const dispTotal   = document.getElementById('display-total');
    let itemIndex     = {{ count($items) }};

    const formatNum = (n) => new Intl.NumberFormat('en-KE', { minimumFractionDigits: 2 }).format(n);

    const refreshRow = (row) => {
        const qty   = parseFloat(row.querySelector('.item-qty')?.value)   || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        const total = qty * price;
        const disp  = row.querySelector('.item-total');
        if (disp) disp.textContent = formatNum(total);
        return total;
    };

    const refreshTotals = () => {
        let sub = 0;
        itemsRoot.querySelectorAll('[data-item-row]').forEach(row => { sub += refreshRow(row); });
        const tax   = parseFloat(taxInput?.value) || 0;
        const total = sub + tax;
        if (dispSub)   dispSub.textContent   = formatNum(sub);
        if (dispTotal) dispTotal.textContent = formatNum(total);
    };

    // Live recalc on any number input
    itemsRoot.addEventListener('input', (e) => {
        if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
            refreshTotals();
        }
    });
    taxInput?.addEventListener('input', refreshTotals);

    // Add item
    addBtn?.addEventListener('click', () => {
        const clone = itemTmpl.content.cloneNode(true);
        const row   = clone.querySelector('[data-item-row]');
        // Set named fields
        row.querySelectorAll('[data-field]').forEach(el => {
            const field = el.dataset.field;
            el.setAttribute('name', `items[${itemIndex}][${field}]`);
        });
        itemsRoot.appendChild(clone);
        itemIndex++;
    });

    // Remove item
    itemsRoot.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-item]');
        if (btn) {
            btn.closest('[data-item-row]')?.remove();
            refreshTotals();
        }
    });

    // Auto-resize
    document.querySelectorAll('textarea.auto-resize').forEach(ta => {
        const fit = () => { ta.style.height = 'auto'; ta.style.height = ta.scrollHeight + 'px'; };
        ta.addEventListener('input', fit);
        fit();
    });

    // Initial totals
    refreshTotals();

    // ── Transaction ledger (statement type) ─────────────────────────────── //
    const txRoot  = document.getElementById('transactions-root');
    const txAdd   = document.getElementById('add-transaction-btn');
    const txTmpl  = document.getElementById('transaction-template');
    let txIndex   = {{ count($transactions) }};

    txAdd?.addEventListener('click', () => {
        const clone = txTmpl.content.cloneNode(true);
        const row   = clone.querySelector('[data-transaction-row]');
        row.querySelectorAll('[data-field]').forEach(el => {
            el.setAttribute('name', `transactions[${txIndex}][${el.dataset.field}]`);
        });
        txRoot.appendChild(clone);
        txIndex++;
    });

    txRoot?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-transaction]');
        if (btn) btn.closest('[data-transaction-row]')?.remove();
    });

    // ── Show only the relevant section for the selected document type ───── //
    const typeSelect = document.querySelector('select[name="type"]');
    typeSelect?.addEventListener('change', () => {
        const isStatement = typeSelect.value === 'statement';
        document.querySelectorAll('[data-doc-type-group="priced"]').forEach(el => {
            el.style.display = isStatement ? 'none' : '';
        });
        document.querySelectorAll('[data-doc-type-group="statement"]').forEach(el => {
            el.style.display = isStatement ? '' : 'none';
        });
    });
});
</script>
@endsection
