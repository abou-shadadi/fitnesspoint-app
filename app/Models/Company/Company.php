<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Company extends Model implements HasMedia
{
    protected $guarded = [];
    use HasFactory, InteractsWithMedia;

    protected $casts = [
        'phone' => 'json'
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();
    }


    public function getLogoAttribute()
    {
        // Get the first media item from the 'featured_image' collection
        $media = $this->getFirstMedia('logo');
        // Return the URL of the media item if it exists, else return a default URL
        return $media ? $media->getUrl() : null;
    }


    public function company_type()
    {
        return $this->belongsTo(CompanyType::class, 'school_type_id');
    }


    public function company_subscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }

}
