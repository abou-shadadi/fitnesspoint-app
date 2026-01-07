<?php

namespace App\Models\Invoice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function rate_type()
    {
        return $this->belongsTo(\App\Models\Rate\RateType::class);
    }
}
