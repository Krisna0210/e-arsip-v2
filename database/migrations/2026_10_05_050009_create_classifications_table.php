<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classifications', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('subject');
            $table->foreignId('parent_id')->nullable()->constrained('classifications')->nullOnDelete();
            $table->integer('active_years')->default(0);
            $table->integer('inactive_years')->default(0);
            $table->enum('outcome', ['musnah', 'permanen', 'dinilai_kembali'])->default('musnah');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classifications');
    }
};
