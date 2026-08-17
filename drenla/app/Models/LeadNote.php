<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A timestamped, staff-authored note against a Client or Inquiry — the "history"
 * CL's flat `notes` text field can't provide. See collaboration-notes.md
 * "Lead/relationship notes structure" decision (2026-07-02, CO) for the rationale.
 */
class LeadNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'notable_type',
        'notable_id',
        'user_id',
        'body',
    ];

    public function notable()
    {
        return $this->morphTo();
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
