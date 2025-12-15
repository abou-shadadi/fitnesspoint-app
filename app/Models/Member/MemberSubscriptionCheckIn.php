<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberSubscriptionCheckIn extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function member_subscription()
    {
        return $this->belongsTo(MemberSubscription::class);
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
