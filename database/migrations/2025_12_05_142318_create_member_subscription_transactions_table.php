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
        Schema::create('member_subscription_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_subscription_id')->constrained();
            $table->string('reference');
            $table->decimal('amount_due', 17, 2);
            $table->decimal('amount_paid', 17, 2);
            $table->timestamp('date');
            $table->foreignId('payment_method_id')->constrained();
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('attachment')->nullable();
            $table->timestamp('current_expiry_date')->nullable();
            $table->timestamp('next_expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'refunded', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_subscription_transactions');
    }
};
