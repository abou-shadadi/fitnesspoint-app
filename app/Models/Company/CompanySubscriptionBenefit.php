<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySubscriptionBenefit extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function benefit()
    {
        return $this->belongsTo(\App\Models\Benefit\Benefit::class);
    }

    public function company_subscription()
    {
        return $this->belongsTo(CompanySubscription::class);
    }
}
