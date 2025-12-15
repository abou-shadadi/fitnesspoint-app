<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function users()
    {
        return $this->hasMany(TaskUser::class);
    }

    public function sub_tasks()
    {
        return $this->hasMany(SubTask::class);
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function user()
    {

        return $this->belongsTo(\App\Models\User::class);
    }

    public function files()
    {

        return $this->hasMany(TaskFile::class);
    }
}
