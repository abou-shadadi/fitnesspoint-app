<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $guarded = [];
    use HasFactory;

    public static function dropdown()
    {
        // Assuming you have a 'phone_code' attribute in your Country model
        return self::pluck('phone_code', 'id');
    }
}
