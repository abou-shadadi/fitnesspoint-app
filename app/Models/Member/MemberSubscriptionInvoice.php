<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// has media 
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MemberSubscriptionInvoice extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = [];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function member_subscription()
    {
        return $this->belongsTo(MemberSubscription::class);
    }

    public function rate_type()
    {
        return $this->belongsTo(\App\Models\Rate\RateType::class);
    }

    public function tax_rate()
    {
        return $this->belongsTo(\App\Models\Invoice\TaxRate::class);
    }



    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile();
    }

    public function getFileAttribute()
    {
        // Get the first media item from the 'featured_image' collection
        $media = $this->getFirstMedia('file');
        // Return the URL of the media item if it exists, else return a default URL
        return $media ? $media->getUrl() : config('app.url') . '/images/file/not_found.png';
    }
}
