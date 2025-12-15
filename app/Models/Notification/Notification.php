<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model {
	protected $guarded = [];
	use HasFactory;

	protected $casts = [
		'data' => 'json',
	];

	public function user() {
		return $this->belongsTo(\App\Models\User::class);
	}

	public function initiated_by() {
		return $this->belongsTo(\App\Models\User::class, 'initiated_by_id');
	}

	public function feature() {
		return $this->belongsTo(\App\Models\Feature::class);
	}
}
