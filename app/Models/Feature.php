<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $guarded = [];
    use HasFactory;


    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }


    /**
     * Get the roles associated with the feature through permissions.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permissions');
    }
}
