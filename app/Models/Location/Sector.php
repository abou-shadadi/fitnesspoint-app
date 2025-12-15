<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function district()
    {

        return $this->belongsTo(District::class);
    }
}
