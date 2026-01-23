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

    protected $appends = [
        'full_phone'
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

    public function getFullPhoneAttribute()
    {
        if (!$this->phone) {
            return null;
        }

        // If phone is a string, return it as is
        if (is_string($this->phone)) {
            return $this->phone;
        }

        // If phone is an array with code and number
        if (is_array($this->phone) && isset($this->phone['number'])) {
            $code = $this->phone['code'] ?? '250';
            $number = $this->phone['number'];
            return "+{$code} {$number}";
        }

        // If phone is an object with code and number properties
        if (is_object($this->phone) && isset($this->phone->number)) {
            $code = $this->phone->code ?? '250';
            $number = $this->phone->number;
            return "+{$code} {$number}";
        }

        return null;
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
