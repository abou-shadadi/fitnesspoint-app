<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $guarded = [];
    use HasFactory;


    public function  user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function files()
    {
        return $this->hasMany(TaskCommentFile::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
