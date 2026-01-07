<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_message',
        'member_import_id',
        'is_resolved',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'is_resolved' => 'boolean',
    ];

    // Relationships
    public function member_import()
    {
        return $this->belongsTo(MemberImport::class);
    }

    // Accessors
    public function getFormattedDataAttribute()
    {
        return $this->data ? json_encode($this->data, JSON_PRETTY_PRINT) : null;
    }
}
