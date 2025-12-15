<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements Auditable, HasMedia {
	use \OwenIt\Auditing\Auditable;
	use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, AuthenticationLoggable;

	/**
	 * Attributes to include in the Audit.
	 *
	 * @var array
	 */
	protected $auditInclude = [
		'email',
	];

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $guarded = [];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [
		'password',
		'remember_token',
		'email_verification_token',
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'email_verified_at' => 'datetime',
		'password' => 'hashed',
		'phone' => 'json',
	];

	public function registerMediaCollections(): void {
		$this->addMediaCollection('avatar')
			->singleFile();
	}

	public function getAvatarAttribute() {
		// Get the first media item from the 'featured_image' collection
		$media = $this->getFirstMedia('avatar');

		// Return the URL of the media item if it exists, else return a default URL
		return $media ? $media->getUrl() : config('app.url') . '/images/user/avatar.png';
	}

	/**
	 * Get the phone attribute value.
	 *
	 * @param  string  $value
	 * @return array|null
	 */
	public function getPhoneAttribute($value) {
		return json_decode($value, true);
	}

	/**
	 * Set the phone attribute value.
	 *
	 * @param  array|string|null  $value
	 * @return void
	 */
	public function setPhoneAttribute($value) {
		$this->attributes['phone'] = is_array($value) ? json_encode($value) : $value;
	}

	/**
	 * Get the address attribute value.
	 *
	 * @param  string  $value
	 * @return array|null
	 */
	public function getAddressAttribute($value) {
		return json_decode($value, true);
	}

	public function role() {
		return $this->belongsTo(Role::class);
	}

	public function notifications() {
		return $this->hasMany(\App\Models\Notification\Notification::class);
    }


    public function user_branches(){
        return $this->hasMany(\App\Models\User\UserBranch::class);
    }
}
