<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySubscriptionInvoice extends Model
{
    use HasFactory;

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
        return $this->belongsTo(\App\Models\Invoice\InvoiceTaxRate::class);
    }


}
