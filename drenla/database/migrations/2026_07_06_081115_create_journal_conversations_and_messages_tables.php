<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The internal Journal — a private, persistent AI-brainstorm space per admin
 * user. Same session/message split as chat_sessions/chat_messages, without
 * the visitor-identity columns a private journal doesn't need. Privacy is
 * enforced in JournalController (ownership checks on every action), not by
 * this schema — route-model binding alone won't stop one user opening
 * another's conversation by URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('content');
            $table->json('tool_call')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_messages');
        Schema::dropIfExists('journal_conversations');
    }
};
