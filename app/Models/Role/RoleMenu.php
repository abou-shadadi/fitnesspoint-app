<?php

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleMenu extends Model
{
    protected $guarded = [];
    use HasFactory;

    protected $attributes = [
        'permissions' => '{"read":true,"create":false,"update":false,"delete":false}',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'order' => 'integer',
        'permissions' => 'array'
    ];

    public function menu()
    {
        return $this->belongsTo(\App\Models\Menu\Menu::class);
    }

    public function role()
    {
        return $this->belongsTo(\App\Models\Role\Role::class);
    }
}
