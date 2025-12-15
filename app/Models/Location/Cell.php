<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cell extends Model
{
    protected $guarded = [];
    public function sector()
    {

        return $this->belongsTo(Sector::class);
    }
}
