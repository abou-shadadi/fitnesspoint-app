<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberImportLog extends Model
{
    protected $fillable = [
        'log_message',
        'student_import_id',
        'is_resolved',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
    ];


    public function student_import()
    {
        return $this->belongsTo(MemberImport::class);
    }
}
