<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'notes')) {
                $table->text('notes')->nullable()->after('subject');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('draft', 'pending_verification', 'verified', 'rejected', 'dispositioned', 'archived', 'waiting_for_file', 'cancelled') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('draft', 'pending_verification', 'verified', 'rejected', 'dispositioned', 'archived', 'waiting_for_file') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
