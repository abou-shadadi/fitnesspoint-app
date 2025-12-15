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
        Schema::create('member_subscription_check_ins', function (Blueprint $table) {
            $table->id();
            $table->timestamp('datetime');

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->name('ms_checkin_branch_fk');

            $table->string('signature')->nullable();

            $table->json('metadata')->nullable();

            $table->foreignId('check_in_method_id')
                ->constrained('check_in_methods')
                ->name('ms_checkin_method_fk');

            $table->foreignId('member_subscription_id')
                ->constrained()
                ->name('ms_checkin_member_subscription_fk');

            $table->enum('status', ['completed', 'failed'])->default('completed');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_subscription_check_ins');
    }
};
