<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySubscriptionTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function payment_method(){
        return $this->belongsTo(\App\Models\Payment\PaymentMethod::class);
    }

    public function branch(){
        return $this->belongsTo(\App\Models\Branch\Branch::class);
    }


    public function created_by(){
        return $this->belongsTo(\App\Models\User::class);
    }


    public function company_subscription(){
        return $this->belongsTo(\App\Models\Company\CompanySubscription::class);
    }

    public function company_subscription_invoice(){
        return $this->belongsTo(\App\Models\Company\CompanySubscriptionInvoice::class);
    }

}
