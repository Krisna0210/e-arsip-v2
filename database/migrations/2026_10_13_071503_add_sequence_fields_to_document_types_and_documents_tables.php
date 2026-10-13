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
        Schema::table('document_types', function (Blueprint $table) {
            $table->unsignedInteger('starting_sequence')->default(0)->after('numbering_format');
            $table->smallInteger('starting_sequence_year')->nullable()->after('starting_sequence');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')->nullable()->index()->after('number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['sequence_number']);
            $table->dropColumn('sequence_number');
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['starting_sequence', 'starting_sequence_year']);
        });
    }
};
