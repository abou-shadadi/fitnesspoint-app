<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('notifications', function (Blueprint $table) {
			$table->id();
			$table->enum('action', ['approve', 'reject', 'create', 'update', 'delete', 'login', 'logout', 'cancel', 'reset', 'assign', 'unassign', 'revoke', 'reassign']);
			$table->foreignId('user_id')->constrained('users');
			$table->foreignId('feature_id')->nullable()->constrained('features');
			$table->json('data')->nullable();
			$table->string('read_at')->nullable();
			$table->foreignId('initiated_by_id')->nullable()->constrained('users');
			$table->boolean('notified_user')->default(0);
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('notifications');
	}
};
