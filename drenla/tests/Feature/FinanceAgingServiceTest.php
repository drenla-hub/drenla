<?php

use App\Models\Client;
use App\Models\FinanceDocument;
use App\Services\FinanceAgingService;

function financeDocumentForAging(array $overrides = []): FinanceDocument
{
    $client = Client::create([
        'name' => 'Mercy Cheru',
        'email' => 'mercy-aging-'.uniqid().'@example.com',
        'status' => 'active',
    ]);

    return FinanceDocument::create([
        'client_id' => $client->id,
        'type' => 'statement',
        'reference_number' => 'DNR-MAY-004-'.uniqid(),
        'currency' => 'KES',
        'issue_date' => now()->toDateString(),
        ...$overrides,
    ]);
}

it('computes the running ledger and aging summary matching the DNR-RCT-KLF 2026 reference statement', function () {
    $document = financeDocumentForAging();

    $document->transactions()->create([
        'transaction_date' => '2025-09-13',
        'label' => 'INV #DNR-SEP-2025',
        'type' => 'invoice',
        'amount' => 580000,
        'sort_order' => 0,
    ]);
    $document->transactions()->create([
        'transaction_date' => '2025-10-15',
        'label' => 'PMNT #0001',
        'type' => 'payment',
        'amount' => 174000,
        'sort_order' => 1,
    ]);

    $service = new FinanceAgingService;
    $rows = $service->ledgerRows($document->fresh(['transactions']));

    expect($rows)->toHaveCount(2);
    expect($rows[0])->toMatchArray([
        'date' => '13-Sep-2025', 'label' => 'INV #DNR-SEP-2025', 'amount' => '580,000.00', 'percent_paid' => '', 'balance' => '580,000.00',
    ]);
    expect($rows[1])->toMatchArray([
        'date' => '15-Oct-2025', 'label' => 'PMNT #0001', 'amount' => '174,000.00', 'percent_paid' => '30%', 'balance' => '406,000.00',
    ]);

    // No due_date set → falls back to issue_date (today, per the helper default),
    // matching the reference: DNR-RCT-KLF 2026's statement is dated the same day it
    // was generated, 0 days past due, so the whole balance is CURRENT — aging is
    // computed against the document's own issue/due date, not the underlying
    // invoice transaction's date.
    $summary = $service->agingSummary($document->fresh(['transactions']));
    expect($summary)->toBe([
        'current' => '406,000.00',
        'days_1_30' => '0.00',
        'days_31_60' => '0.00',
        'days_61_90_plus' => '0.00',
        'total' => '406,000.00',
    ]);
});

it('buckets an outstanding balance into the correct aging window based on days past due', function () {
    $document = financeDocumentForAging(['due_date' => now()->subDays(45)->toDateString()]);

    $document->transactions()->create([
        'transaction_date' => now()->subDays(90)->toDateString(),
        'label' => 'INV #TEST-001',
        'type' => 'invoice',
        'amount' => 100000,
        'sort_order' => 0,
    ]);

    $summary = (new FinanceAgingService)->agingSummary($document->fresh(['transactions']));

    expect($summary['days_31_60'])->toBe('100,000.00');
    expect($summary['current'])->toBe('0.00');
    expect($summary['total'])->toBe('100,000.00');
});

it('places a fully paid document entirely in the current bucket with a zero balance', function () {
    $document = financeDocumentForAging(['due_date' => now()->subDays(90)->toDateString()]);

    $document->transactions()->create([
        'transaction_date' => now()->subDays(100)->toDateString(),
        'label' => 'INV #TEST-002',
        'type' => 'invoice',
        'amount' => 50000,
        'sort_order' => 0,
    ]);
    $document->transactions()->create([
        'transaction_date' => now()->subDays(10)->toDateString(),
        'label' => 'PMNT #FULL',
        'type' => 'payment',
        'amount' => 50000,
        'sort_order' => 1,
    ]);

    $summary = (new FinanceAgingService)->agingSummary($document->fresh(['transactions']));

    expect($summary['total'])->toBe('0.00');
    expect($summary['current'])->toBe('0.00');
});
