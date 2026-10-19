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
        Schema::create('document_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_code')->unique()->index();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('borrow_date');
            $table->date('expected_return_date');
            $table->date('returned_date')->nullable();
            $table->text('purpose');
            $table->text('notes')->nullable();
            $table->enum('status', ['requested', 'borrowed', 'returned', 'rejected', 'cancelled'])->default('requested')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_loans');
    }
};
