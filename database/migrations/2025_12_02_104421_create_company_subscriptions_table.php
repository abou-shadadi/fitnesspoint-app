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

        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->decimal('unit_price', 17, 2);
            $table->foreignId('currency_id')->constrained();
            $table->foreignId('duration_type_id')->constrained();
            $table->foreignId('billing_type_id')->constrained();
            $table->integer('initial_count')->default(1); // added this now
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('attachment')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignId('branch_id')->constrained('branches');
            $table->enum('status', ['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};
