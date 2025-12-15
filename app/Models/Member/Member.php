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

    /**
     * Get the province of the member.
     */
    public function province()
    {
        return $this->belongsTo(\App\Models\Location\Province::class);
    }

    /**
     * Get the district of the member.
     */
    public function district()
    {
        return $this->belongsTo(\App\Models\Location\District::class);
    }

    /**
     * Get the sector of the member.
     */
    public function sector()
    {
        return $this->belongsTo(\App\Models\Location\Sector::class);
    }

    /**
     * Get the cell of the member.
     */
    public function cell()
    {
        return $this->belongsTo(\App\Models\Location\Cell::class);
    }

    /**
     * Get the village of the member.
     */
    public function village()
    {
        return $this->belongsTo(\App\Models\Location\Village::class);
    }

    /**
     * Get the previous qualifications of the member.
     */

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


    // public function member_import_other_column_values()
    // {
    //     return $this->hasMany(MemberImportOtherColumnValue::class);
    // }


    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

}
