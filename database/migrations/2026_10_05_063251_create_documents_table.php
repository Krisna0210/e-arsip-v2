<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['incoming', 'outgoing', 'internal'])->default('incoming');
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->nullable(); // external number or generated number
            $table->date('document_date');
            $table->date('received_date')->nullable();
            $table->string('sender')->nullable(); // For incoming
            $table->string('subject');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete(); // Owned by which unit
            $table->foreignId('classification_id')->nullable()->constrained('classifications')->nullOnDelete();
            $table->enum('sifat', ['biasa', 'penting', 'rahasia'])->default('biasa');
            $table->enum('status', ['draft', 'pending_verification', 'verified', 'rejected', 'dispositioned', 'archived', 'waiting_for_file'])->default('draft');
            $table->enum('archive_status', ['active', 'inactive', 'disposed'])->default('active');
            $table->unsignedBigInteger('current_version_id')->nullable(); 
            $table->date('retention_start_date')->nullable();
            $table->date('retention_end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('documents');
    }
};
