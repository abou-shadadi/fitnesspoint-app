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
        Schema::create('member_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file');
            $table->string('failed_import_file')->nullable();
            $table->string('imported_file')->nullable();
            $table->foreignId('company_id')->nullable()->constrained(); // not mandotory
            $table->foreignId('branch_id')->constrained('branches'); // always to be provided
            $table->foreignId('created_by_id')->constrained('users'); // from Auth::user()
            $table->json('data')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_imports');
    }
};
