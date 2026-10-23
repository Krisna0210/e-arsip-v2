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
        Schema::table('documents', function (Blueprint $table) {
            $table->index('number', 'idx_documents_number');
            $table->index('document_date', 'idx_documents_document_date');
            $table->index('status', 'idx_documents_status');
            $table->index('sifat', 'idx_documents_sifat');
            $table->index(['type', 'status'], 'idx_documents_type_status');
            $table->index(['status', 'document_date'], 'idx_documents_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_documents_number');
            $table->dropIndex('idx_documents_document_date');
            $table->dropIndex('idx_documents_status');
            $table->dropIndex('idx_documents_sifat');
            $table->dropIndex('idx_documents_type_status');
            $table->dropIndex('idx_documents_status_date');
        });
    }
};
