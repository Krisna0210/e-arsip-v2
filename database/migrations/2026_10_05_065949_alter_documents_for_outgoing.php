<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('recipient_name')->nullable()->after('sender');
            $table->foreignId('signer_id')->nullable()->after('folder_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['signer_id']);
            $table->dropColumn(['recipient_name', 'signer_id']);
        });
    }
};