<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberImportOtherColumnValue extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function column()
    {
        return $this->belongsTo(MemberImportOtherColumn::class, 'student_import_other_column_id');
    }

    public function student()
    {
        return $this->belongsTo(Member::class);
    }
}
