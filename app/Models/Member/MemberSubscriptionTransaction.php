<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberSubscriptionTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function member_subscription()
    {
        return $this->belongsTo(MemberSubscription::class);
    }

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function payment_method()
    {
        return $this->belongsTo(\App\Models\Payment\PaymentMethod::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch\Branch::class);
    }

    

}
