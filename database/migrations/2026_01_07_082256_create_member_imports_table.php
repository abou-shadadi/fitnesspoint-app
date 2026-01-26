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

            $table->foreignId('branch_id')->constrained('branches'); // always to be provided
            // member_subscription_id
            $table->foreignId('plan_id')->nullable()->constrained();
            // company_subscription_id
            $table->foreignId('company_subscription_id')->nullable()->constrained();

            $table->foreignId('created_by_id')->constrained('users'); // from Auth::user()
            $table->json('data')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'completed_with_errors'])->default('pending');
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
