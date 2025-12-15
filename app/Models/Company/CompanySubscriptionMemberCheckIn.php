<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySubscriptionMemberCheckIn extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function company_subscription_member()
    {
        return $this->belongsTo(\App\Models\Company\CompanySubscriptionMember::class);
    }

    public function check_in_method()
    {
        return $this->belongsTo(\App\Models\CheckIn\CheckInMethod::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch\Branch::class);
    }

    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

}
