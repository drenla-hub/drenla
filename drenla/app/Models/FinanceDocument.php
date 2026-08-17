<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'proposal_id',
        'project_id',
        'type',
        'status',
        'reference_number',
        'currency',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'notes',
        'payment_terms',
        'payment_info',
        'next_due_label',
        'next_due_amount',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'next_due_amount' => 'decimal:2',
        ];
    }

    protected $appends = [
        'balance',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(FinanceDocumentItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(FinanceDocumentTransaction::class);
    }

    public function getBalanceAttribute(): string
    {
        return number_format(max(0, (float) $this->total_amount - (float) $this->amount_paid), 2, '.', '');
    }

    /** Recalculate subtotal and total from line items (chainable). */
    public function recalculate(): static
    {
        $subtotal = $this->items()->sum('total');
        $this->subtotal = round($subtotal, 2);
        $this->total_amount = round($subtotal + (float) $this->tax_amount, 2);

        return $this;
    }
}
