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
        Schema::table('document_loans', function (Blueprint $table) {
            $table->index(['document_id', 'status'], 'idx_document_loans_doc_status');
            $table->index(['user_id', 'status'], 'idx_document_loans_user_status');
            $table->index(['status', 'updated_at'], 'idx_document_loans_status_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_loans', function (Blueprint $table) {
            $table->dropIndex('idx_document_loans_doc_status');
            $table->dropIndex('idx_document_loans_user_status');
            $table->dropIndex('idx_document_loans_status_updated');
        });
    }
};
