<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function province()
    {

        return $this->belongsTo(Province::class);
    }
}
