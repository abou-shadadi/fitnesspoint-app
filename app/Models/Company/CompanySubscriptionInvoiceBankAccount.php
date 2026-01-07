<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySubscriptionInvoiceBankAccount extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function bank_account()
    {
        return $this->belongsTo(\App\Models\Bank\BankAccount::class);
    }

    public function company_subscription_invoice()
    {
        return $this->belongsTo(\App\Models\Company\CompanySubscriptionInvoice::class);
    }
}
