<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySubscriptionMember extends Model
{
    use HasFactory;
    protected$guarded = [];

    public function member()
    {
        return $this->belongsTo(\App\Models\Member\Member::class);
    }

    public function company_subscription()
    {
        return $this->belongsTo(\App\Models\Company\CompanySubscription::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company\Company::class);
    }

    public function company_subscription_member_check_ins()
    {
        return $this->hasMany(\App\Models\Company\CompanySubscriptionMemberCheckIn::class);
    }
}
