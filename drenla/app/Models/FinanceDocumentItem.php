<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceDocumentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_document_id',
        'title',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function financeDocument()
    {
        return $this->belongsTo(FinanceDocument::class);
    }
}
