<?php

namespace App\Models\Plan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanBenefit extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function benefit()
    {
        return $this->belongsTo(\App\Models\Benefit\Benefit::class);
    }
}
