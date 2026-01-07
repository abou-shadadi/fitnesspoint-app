<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberImport extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch\Branch::class);
    }

    public function member_import_logs()
    {
        return $this->hasMany(MemberImportLog::class);
    }

    // Accessors
    public function getFileUrlAttribute()
    {
        return $this->file ? storage_path('app/' . $this->file) : null;
    }

    public function getFailedImportFileUrlAttribute()
    {
        return $this->failed_import_file ? storage_path('app/' . $this->failed_import_file) : null;
    }

    public function getImportedFileUrlAttribute()
    {
        return $this->imported_file ? storage_path('app/' . $this->imported_file) : null;
    }
}
