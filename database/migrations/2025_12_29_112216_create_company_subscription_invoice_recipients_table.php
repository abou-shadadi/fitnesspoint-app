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
        Schema::create('company_subscription_invoice_recipients', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_subscription_invoice_id');
            $table->foreign('company_subscription_invoice_id', 'csir_invoice_id_fk')
                ->references('id')
                ->on('company_subscription_invoices')
                ->onDelete('cascade');

            $table->unsignedBigInteger('company_administrator_id');
            $table->foreign('company_administrator_id', 'csir_admin_id_fk')
                ->references('id')
                ->on('company_administrators');

            $table->boolean('is_primary')->default(false);
            $table->boolean('is_sent')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscription_invoice_recipients');
    }
};
