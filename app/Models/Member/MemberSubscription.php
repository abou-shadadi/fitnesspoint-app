<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberSubscription extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function plan()
    {
        return $this->belongsTo(\App\Models\Plan\Plan::class);
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
