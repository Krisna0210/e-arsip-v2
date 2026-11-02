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
        Schema::table('digital_documents', function (Blueprint $table) {
            $table->enum('access_type', ['public', 'private'])->default('public')->after('category')->index();
        });

        Schema::create('digital_document_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_document_id')->constrained('digital_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->unsignedInteger('duration_hours')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['digital_document_id', 'user_id', 'status'], 'dig_doc_req_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_document_access_requests');

        Schema::table('digital_documents', function (Blueprint $table) {
            $table->dropColumn('access_type');
        });
    }
};
