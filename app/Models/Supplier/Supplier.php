<?php

namespace App\Models\Supplier;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = [];
    use HasFactory;


    public function created_by()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    public function supplier_type()
    {
        return $this->belongsTo(SupplierType::class);
    }
}
