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
        Schema::create('company_subscription_invoice_bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_subscription_invoice_id');
            $table->unsignedBigInteger('bank_account_id');

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('company_subscription_invoice_id', 'fk_csiba_invoice')
                ->references('id')
                ->on('company_subscription_invoices')
                ->cascadeOnDelete();

            $table->foreign('bank_account_id', 'fk_csiba_bank')
                ->references('id')
                ->on('bank_accounts')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscription_invoice_banks');
    }
};
