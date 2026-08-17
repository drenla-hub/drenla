<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'media_asset_id',
        'title',
        'is_client_visible',
        'sort_order',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_client_visible' => 'boolean',
        ];
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function mediaAsset()
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: $this->mediaAsset?->title ?? 'File';
    }
}
