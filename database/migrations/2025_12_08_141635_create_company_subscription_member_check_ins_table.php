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
        Schema::create('company_subscription_member_check_ins', function (Blueprint $table) {
            $table->id();
            $table->timestamp('datetime');
            $table->text('notes')->nullable();

            $table->foreignId('created_by_id')
                ->constrained('users')
                ->name('csm_checkin_created_by_fk');

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->name('csm_checkin_branch_fk');

            $table->longText('signature')->nullable();

            $table->json('metadata')->nullable();

            $table->foreignId('check_in_method_id')
                ->constrained('check_in_methods')
                ->name('csm_checkin_method_fk');

            $table->foreignId('company_subscription_member_id')
                ->constrained()
                ->name('csm_checkin_member_fk');

            $table->enum('status', ['pending', 'completed', 'failed'])
                ->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscription_member_check_ins');
    }
};
