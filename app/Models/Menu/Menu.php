<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function menu_group()
    {
        return $this->belongsTo(MenuGroup::class);
    }


    public function roles()
    {
        return $this->belongsToMany(\App\Models\Role\Role::class, 'role_menus', 'menu_id', 'role_id')
            ->withPivot('status', 'created_at', 'updated_at');
    }


    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }
}
