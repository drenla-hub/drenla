<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceDocumentTransaction extends Model
{
    public const TYPE_INVOICE = 'invoice';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_CREDIT = 'credit';

    protected $fillable = [
        'finance_document_id',
        'transaction_date',
        'label',
        'type',
        'amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function financeDocument()
    {
        return $this->belongsTo(FinanceDocument::class);
    }

    /** Invoice rows add to the outstanding balance; everything else reduces it. */
    public function isInvoice(): bool
    {
        return $this->type === self::TYPE_INVOICE;
    }
}
