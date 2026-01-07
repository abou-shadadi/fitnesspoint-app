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
        Schema::create('company_subscription_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference');

            // Invoice FK (manual to avoid long name)
            $table->unsignedBigInteger('company_subscription_invoice_id');
            $table->foreign('company_subscription_invoice_id', 'cst_invoice_fk')
                ->references('id')
                ->on('company_subscription_invoices')
                ->cascadeOnDelete();

            $table->foreignId('company_subscription_id')
                ->constrained()
                ->name('cst_company_subscription_fk');

            $table->foreignId('payment_method_id')
                ->constrained()
                ->name('cst_payment_method_fk');

            $table->decimal('amount_due', 17, 2);
            $table->decimal('amount_paid', 17, 2);
            $table->timestamp('date');

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->name('cst_branch_fk');

            $table->string('attachment')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by_id')
                ->constrained('users')
                ->name('cst_created_by_fk');

            $table->timestamp('current_expiry_date')->nullable();
            $table->timestamp('next_expiry_date')->nullable();

            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'cancelled',
                'refunded',
                'rejected'
            ])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscription_transactions');
    }
};
