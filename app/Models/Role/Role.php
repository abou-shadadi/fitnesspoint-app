<?php

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function permissions()
    {
        return $this->hasMany(\App\Models\Permission::class);
    }


    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function role_menus()
    {
        return $this->hasMany(\App\Models\Role\RoleMenu::class);
    }
}
