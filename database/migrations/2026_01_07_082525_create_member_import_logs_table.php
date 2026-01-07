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
        Schema::create('member_import_logs', function (Blueprint $table) {
            $table->id();
            $table->longText('log_message')->nullable();
            $table->foreignId('membery_import_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_resolved')->default(false);
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_import_logs');
    }
};
