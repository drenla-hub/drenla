<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_documents', function (Blueprint $table) {
            $table->text('payment_terms')->nullable()->after('notes');
            $table->text('payment_info')->nullable()->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('finance_documents', function (Blueprint $table) {
            $table->dropColumn(['payment_terms', 'payment_info']);
        });
    }
};
