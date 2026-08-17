<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two additions needed to turn the chat audit trail into a live, admin-takeover-
 * capable conversation: `ai_enabled` lets a session be flipped to "human
 * handling" the moment an admin replies (so the AI stops talking over them),
 * and `user_id` records exactly which admin sent an admin-authored message —
 * the "clear note of who is sending messages" requirement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(true)->after('context');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn('ai_enabled');
        });
    }
};
