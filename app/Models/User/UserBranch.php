<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBranch extends Model
{
    use HasFactory;
    protected $guarded = [];



    public function user(){
        return $this->belongsTo(\App\Models\User::class);
    }

    public function branch(){
        return $this->belongsTo(\App\Models\Branch\Branch::class);
    }
}
