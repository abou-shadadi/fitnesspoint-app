<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubTaskComment extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function  user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function sub_task()
    {
        return $this->belongsTo(SubTask::class);
    }

    public function files()
    {

        return $this->hasMany(SubTaskCommentFile::class);
    }
}
