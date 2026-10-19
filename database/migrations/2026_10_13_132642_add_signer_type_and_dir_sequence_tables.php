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
            $table->enum('signer_type', ['pimpinan_unit', 'direktur'])->default('pimpinan_unit')->index()->after('recipient_name');
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->unsignedInteger('starting_sequence_dir')->default(0)->after('starting_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['signer_type']);
            $table->dropColumn('signer_type');
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('starting_sequence_dir');
        });
    }
};
