<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'company',
        'phone',
        'subject',
        'message',
        'status',
        'notes',
        'assigned_to',
        'client_id',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function leadNotes()
    {
        return $this->morphMany(LeadNote::class, 'notable')->latest('id');
    }
}
