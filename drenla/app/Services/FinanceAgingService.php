<?php

namespace App\Services;

use App\Models\FinanceDocument;
use App\Models\FinanceDocumentTransaction;

/**
 * Turns a FinanceDocument's itemized transaction history (invoices, payments,
 * credits) into the two things the statement/aging-table document family
 * needs: a running-balance ledger (DATE / TRANSACTION / AMOUNT / %PAY /
 * BALANCE, matching the DNR-RCT-KLF reference) and an aging summary bucketed
 * by days past due (CURRENT / 1-30 / 31-60 / 61-90+ / TOTAL).
 *
 * Aging is computed against the whole outstanding balance relative to the
 * document's own due_date (falling back to issue_date) — not a per-invoice
 * FIFO allocation across multiple invoice rows. The one real reference
 * (DNR-RCT-KLF 2026) only ever shows a single invoice per statement, so a
 * per-transaction aging model would be speculative complexity with nothing to
 * verify it against; this covers the documented case exactly and is the
 * simplest thing that could be correct for it.
 */
class FinanceAgingService
{
    /** @return array<int, array{date: string, label: string, type: string, amount: string, percent_paid: string, balance: string}> */
    public function ledgerRows(FinanceDocument $document): array
    {
        $balance = 0.0;
        $invoiceTotal = 0.0;
        $rows = [];

        foreach ($this->orderedTransactions($document) as $transaction) {
            $amount = (float) $transaction->amount;
            $percentPaid = '';

            if ($transaction->isInvoice()) {
                $balance += $amount;
                $invoiceTotal += $amount;
            } else {
                $balance -= $amount;
                if ($invoiceTotal > 0) {
                    $percentPaid = round(($amount / $invoiceTotal) * 100).'%';
                }
            }

            $rows[] = [
                'date' => $transaction->transaction_date->format('d-M-Y'),
                'label' => $transaction->label,
                'type' => $transaction->type,
                'amount' => number_format($amount, 2),
                'percent_paid' => $percentPaid,
                'balance' => number_format(max(0, $balance), 2),
            ];
        }

        return $rows;
    }

    /** @return array{current: string, days_1_30: string, days_31_60: string, days_61_90_plus: string, total: string} */
    public function agingSummary(FinanceDocument $document): array
    {
        $balance = $this->outstandingBalance($document);
        $daysPastDue = $this->daysPastDue($document);

        $buckets = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90_plus' => 0.0,
        ];

        if ($balance > 0) {
            $bucket = match (true) {
                $daysPastDue <= 0 => 'current',
                $daysPastDue <= 30 => 'days_1_30',
                $daysPastDue <= 60 => 'days_31_60',
                default => 'days_61_90_plus',
            };
            $buckets[$bucket] = $balance;
        }

        return [
            'current' => number_format($buckets['current'], 2),
            'days_1_30' => number_format($buckets['days_1_30'], 2),
            'days_31_60' => number_format($buckets['days_31_60'], 2),
            'days_61_90_plus' => number_format($buckets['days_61_90_plus'], 2),
            'total' => number_format(array_sum($buckets), 2),
        ];
    }

    public function outstandingBalance(FinanceDocument $document): float
    {
        return $this->orderedTransactions($document)
            ->sum(fn (FinanceDocumentTransaction $t) => $t->isInvoice() ? (float) $t->amount : -(float) $t->amount);
    }

    private function daysPastDue(FinanceDocument $document): int
    {
        $reference = $document->due_date ?? $document->issue_date;

        if (! $reference) {
            return 0;
        }

        $today = now()->startOfDay();
        $reference = $reference->copy()->startOfDay();

        // Carbon 3's diffInDays() defaults to a signed difference, not absolute
        // (contrary to older Carbon versions) — pass $absolute explicitly so this
        // doesn't silently flip sign on a Carbon upgrade.
        return $today->lte($reference) ? 0 : $today->diffInDays($reference, absolute: true);
    }

    private function orderedTransactions(FinanceDocument $document)
    {
        return $document->transactions->sortBy([['transaction_date', 'asc'], ['sort_order', 'asc']])->values();
    }
}
