<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('available_hardcopy')->default(true)->after('archive_status');
            $table->boolean('available_softcopy')->default(false)->after('available_hardcopy');
        });

        // Update existing documents that already have an uploaded scan/version to available_softcopy = true
        DB::table('documents')
            ->whereNotNull('current_version_id')
            ->update(['available_softcopy' => true]);

        Schema::table('document_loans', function (Blueprint $table) {
            $table->string('loan_type', 20)->default('hardcopy')->after('status');
            $table->unsignedInteger('duration_hours')->nullable()->after('loan_type');
            $table->timestamp('access_expires_at')->nullable()->after('duration_hours');
        });
    }

    public function down(): void
    {
        Schema::table('document_loans', function (Blueprint $table) {
            $table->dropColumn(['loan_type', 'duration_hours', 'access_expires_at']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['available_hardcopy', 'available_softcopy']);
        });
    }
};
