<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuGroup extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function menus(){
        return $this->hasMany(Menu::class);
    }
}
