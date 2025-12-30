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
        Schema::create('company_subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('company_subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('rate_type_id')->constrained();
            $table->foreignId('tax_rate_id')->constrained();
            $table->timestamp('from_date');
            $table->timestamp('to_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->date('invoice_date');
            $table->boolean('is_sent')->default(false);
            $table->string('file')->nullable();
            $table->text('notes')->nullable();
            $table->integer('total_member_check_ins')->default(0);
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled', 'refunded', 'rejected', 'partially_paid'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscription_invoices');
    }
};
