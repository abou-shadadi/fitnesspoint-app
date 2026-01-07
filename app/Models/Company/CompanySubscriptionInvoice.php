<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CompanySubscriptionInvoice extends Model implements HasMedia {

    use HasFactory, InteractsWithMedia;

    protected $guarded = [];
    protected $casts = [
        'from_date' => 'datetime',
        'to_date' => 'datetime',
        'due_date' => 'date',
        'invoice_date' => 'date',
    ];

    public function company_subscription()
    {
        return $this->belongsTo(CompanySubscription::class);
    }
    public function rate_type()
    {
        return $this->belongsTo(\App\Models\Rate\RateType::class);
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class);
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


    public function company_subscription_invoice_recipients()
    {
        return $this->hasMany(CompanySubscriptionInvoiceRecipient::class);
    }

    public function company_subscription_invoice_bank_accounts(){
        return $this->hasMany(\App\Models\Company\CompanySubscriptionInvoiceBankAccount::class);
    }

}
