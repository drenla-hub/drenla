<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_document_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_document_id')->constrained('finance_documents')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('label');
            // 'invoice' rows add to the running balance, 'payment'/'credit' rows reduce it.
            $table->string('type')->default('payment');
            $table->decimal('amount', 14, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Statement documents show a "next due" caption below the aging summary
        // (e.g. "PHASE 3 - INV #X - DUE PAYMENT ... DUE AMOUNT: 116,000.00") — kept
        // as plain admin-entered fields rather than derived from payment_terms text,
        // since the phase/stage split isn't itemized data anywhere else yet.
        Schema::table('finance_documents', function (Blueprint $table) {
            $table->string('next_due_label')->nullable()->after('payment_info');
            $table->decimal('next_due_amount', 14, 2)->nullable()->after('next_due_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_documents', function (Blueprint $table) {
            $table->dropColumn(['next_due_label', 'next_due_amount']);
        });

        Schema::dropIfExists('finance_document_transactions');
    }
};
