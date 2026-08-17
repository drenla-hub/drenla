<?php

namespace App\Models;

use App\Services\ProposalReferenceGenerator;
use App\Support\ProposalDocumentData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'created_by',
        'title',
        'slug',
        'status',
        'template_key',
        'template_version',
        'document_status',
        'reference_number',
        'issue_date',
        'summary',
        'body',
        'document_data',
        'value',
        'is_client_visible',
        'access_token',
        'last_exported_at',
        'last_exported_filename',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'is_client_visible' => 'boolean',
            'issue_date' => 'date',
            'document_data' => 'array',
            'last_exported_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $proposal): void {
            if ($proposal->template_key === null || $proposal->template_key === '') {
                $proposal->template_key = ProposalDocumentData::TEMPLATE_KEY;
            }

            if ($proposal->template_version === null || $proposal->template_version === '') {
                $proposal->template_version = ProposalDocumentData::TEMPLATE_VERSION;
            }

            if ($proposal->document_status === null || $proposal->document_status === '') {
                $proposal->document_status = 'draft';
            }

            if ($proposal->slug === null || $proposal->slug === '') {
                $proposal->slug = Str::slug($proposal->title);
            }

            if ($proposal->reference_number === null || $proposal->reference_number === '') {
                $proposal->reference_number = app(ProposalReferenceGenerator::class)->generate(
                    $proposal->issue_date,
                );
            }

            if (($proposal->access_token === null || $proposal->access_token === '') && $proposal->is_client_visible) {
                $proposal->access_token = Str::random(32);
            }
        });
    }

    public function getNormalizedDocumentDataAttribute(): array
    {
        return ProposalDocumentData::normalize($this->document_data, $this);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function financeDocuments()
    {
        return $this->hasMany(FinanceDocument::class);
    }

    /** The most recent linked quotation (for PDF injection). */
    public function quotation()
    {
        return $this->hasOne(FinanceDocument::class)
            ->where('type', 'quotation')
            ->latest();
    }

    public function files()
    {
        return $this->hasMany(ProposalFile::class)->orderBy('sort_order');
    }
}
