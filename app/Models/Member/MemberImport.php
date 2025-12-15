<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberImport extends Model
{
    protected $guarded = [];
    use HasFactory;


    // append the file path



    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }


    // public function filePathAttribute()
    // {
    //     return Storage::url($this->file);
    // }


    public function import_logs(){
        return $this->hasMany(\App\Models\Member\MemberImportLog::class);
    }
}
