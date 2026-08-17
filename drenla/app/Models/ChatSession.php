<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $fillable = [
        'session_token',
        'title',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'ip_address',
        'user_agent',
        'country',
        'city',
        'context',
        'last_message_at',
        'ai_enabled',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'last_message_at' => 'datetime',
            'ai_enabled' => 'boolean',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }
}
