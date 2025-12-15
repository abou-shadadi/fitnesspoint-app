<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberImportOtherColumn extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function values()
    {
        return $this->hasMany(MemberImportOtherColumnValue::class);
    }
}
