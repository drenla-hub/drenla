<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JournalMessage extends Model
{
    protected $fillable = ['journal_conversation_id', 'role', 'content', 'tool_call'];

    public function attachments(): HasMany
    {
        return $this->hasMany(JournalMessageAttachment::class);
    }

    protected function casts(): array
    {
        return [
            'tool_call' => 'array',
        ];
    }

    /**
     * Trim at the boundary so every consumer — the transcript, the copy
     * button, and the history array sent back to the AI — sees clean text
     * without stray leading/trailing blank lines.
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => trim($value),
            set: fn (string $value) => trim($value),
        );
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(JournalConversation::class);
    }

    /**
     * Assistant replies are authored as Markdown and rendered to HTML; user
     * input is displayed verbatim (escaped) so nothing they type is ever
     * interpreted as markup.
     */
    public function renderedContent(): string
    {
        if ($this->role !== 'assistant') {
            return nl2br(e($this->content));
        }

        return Str::markdown($this->content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
