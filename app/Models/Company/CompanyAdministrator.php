<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyAdministrator extends Model
{
    protected $guarded = [];
    use HasFactory;

    protected $casts = [
        'phone' => 'json'
    ];


    public function designation()
    {

        return $this->belongsTo(CompanyDesignation::class, 'school_designation_id');
    }
}
