<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySubscriptionInvoiceRecipient extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function company_subscription_invoice()
    {
        return $this->belongsTo(CompanySubscriptionInvoice::class);
    }

    public function company_administrator()
    {
        return $this->belongsTo(CompanyAdministrator::class);
    }
}
