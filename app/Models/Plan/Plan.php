<?php

namespace App\Models\Plan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'description',
        'price',
        'currency_id',
        'duration',
        'duration_type_id',
        'features',
        'status',
    ];

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class);
    }

    public function duration_type()
    {
        return $this->belongsTo(\App\Models\Duration\DurationType::class);
    }

    public function benefits()
     {
        return $this->hasMany(PlanBenefit::class);
    }
}
