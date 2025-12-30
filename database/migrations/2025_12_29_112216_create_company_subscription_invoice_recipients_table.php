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
            $table->foreignId('company_subscription_invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_adminstrator_id')->constrained();
            // is primary
            $table->boolean('is_primary')->default(false);
            // is senbt
            $table->boolean('is_sent')->default(false);
            // satus
            $table->enum('status', ['active', 'inactive'])->default('pending');
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
