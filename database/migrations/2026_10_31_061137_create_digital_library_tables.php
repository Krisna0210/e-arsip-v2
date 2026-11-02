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
        Schema::create('digital_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('digital_folders')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('digital_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('document_number', 50)->unique();
            $table->enum('category', [
                'As-Built Drawing',
                'Kontrak/Perjanjian',
                'Dokumen Hukum/Aset',
                'Laporan Proyek',
                'Manual/Pedoman'
            ]);
            $table->unsignedSmallInteger('year');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->text('tags')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('folder_id')->nullable()->constrained('digital_folders')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['year', 'category']);
            $table->index('unit_id');
            $table->index('folder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_documents');
        Schema::dropIfExists('digital_folders');
    }
};
