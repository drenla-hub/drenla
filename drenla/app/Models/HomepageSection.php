<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'eyebrow',
        'title',
        'body',
        'cta_label',
        'cta_url',
        'media_asset_id',
        'payload',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function mediaAsset()
    {
        return $this->belongsTo(MediaAsset::class);
    }
}
