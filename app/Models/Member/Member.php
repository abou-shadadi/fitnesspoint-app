<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Member extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [

        'phone' => 'json'

    ];


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


    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile();
    }

    public function getImageAttribute()
    {
        // Get the first media item from the 'featured_image' collection
        $media = $this->getFirstMedia('image');
        // Return the URL of the media item if it exists, else return a default URL
        return $media ? $media->getUrl() : config('app.url') . '/images/user/avatar.png';
    }


    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Member\MemberSubscription::class);
    }
}
