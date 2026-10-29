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
            if (!Schema::hasColumn('documents', 'cancellation_requested_at')) {
                $table->timestamp('cancellation_requested_at')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('documents', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancellation_requested_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('documents', 'cancellation_rejection_notes')) {
                $table->text('cancellation_rejection_notes')->nullable()->after('cancelled_by');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('draft', 'pending_verification', 'verified', 'rejected', 'dispositioned', 'archived', 'waiting_for_file', 'cancelled', 'cancellation_requested') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('draft', 'pending_verification', 'verified', 'rejected', 'dispositioned', 'archived', 'waiting_for_file', 'cancelled') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'cancellation_rejection_notes')) {
                $table->dropColumn('cancellation_rejection_notes');
            }
            if (Schema::hasColumn('documents', 'cancelled_by')) {
                $table->dropForeign(['cancelled_by']);
                $table->dropColumn('cancelled_by');
            }
            if (Schema::hasColumn('documents', 'cancellation_requested_at')) {
                $table->dropColumn('cancellation_requested_at');
            }
        });
    }
};
