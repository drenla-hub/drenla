<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CaseStudy extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'client_name',
        'industry',
        'summary',
        'body',
        'media_asset_id',
        'content_blocks',
        'gallery',
        'featured',
        'sort_order',
        'status',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'content_blocks' => 'array',
            'gallery' => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $caseStudy): void {
            if ($caseStudy->slug === null || $caseStudy->slug === '') {
                $caseStudy->slug = Str::slug($caseStudy->title);
            }
        });
    }

    public function mediaAsset()
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', 'published')
            ->where(function ($inner) {
                $inner->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }
}
