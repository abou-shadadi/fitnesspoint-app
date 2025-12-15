<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CompanySubscription extends Model  implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company\Company::class);
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class);
    }

    public function duration_type()
    {
        return $this->belongsTo(\App\Models\Duration\DurationType::class);
    }

    public function billing_type()
    {
        return $this->belongsTo(\App\Models\Billing\BillingType::class);
    }


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachment')
            ->singleFile();
    }

    public function getAttachmentAttribute()
    {
        // Get the first media item from the 'featured_image' collection
        $media = $this->getFirstMedia('attachment');
        // Return the URL of the media item if it exists, else return a default URL
        return $media ? $media->getUrl() : null;
    }


    public function benefits()
    {
        return $this->hasMany(CompanySubscriptionBenefit::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch\Branch::class);
    }

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function company_subscription_members()
    {
        return $this->hasMany(CompanySubscriptionMember::class);
    }
}
