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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('image')->nullable();
            $table->string('national_id_number')->nullable();
            $table->json('phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->foreignId('province_id')->nullable()->constrained();
            $table->foreignId('district_id')->nullable()->constrained();
            $table->foreignId('sector_id')->nullable()->constrained();
            $table->foreignId('cell_id')->nullable()->constrained();
            $table->foreignId('village_id')->nullable()->constrained();
            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
