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
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('template_key')->default('drenla_project_brief')->after('status');
            $table->string('template_version')->default('v1')->after('template_key');
            $table->string('document_status')->default('draft')->after('template_version');
            $table->string('reference_number')->nullable()->unique()->after('document_status');
            $table->date('issue_date')->nullable()->after('reference_number');
            $table->json('document_data')->nullable()->after('body');
            $table->timestamp('last_exported_at')->nullable()->after('access_token');
            $table->string('last_exported_filename')->nullable()->after('last_exported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropUnique(['reference_number']);
            $table->dropColumn([
                'template_key',
                'template_version',
                'document_status',
                'reference_number',
                'issue_date',
                'document_data',
                'last_exported_at',
                'last_exported_filename',
            ]);
        });
    }
};
