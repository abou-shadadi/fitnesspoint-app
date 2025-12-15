<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubTask extends Model
{
    protected $guarded = [];
    use HasFactory;



    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function comments()
    {
        return $this->hasMany(SubTaskComment::class);
    }


    public function user()
    {

        return $this->belongsTo(\App\Models\User::class);
    }


    public function users()
    {
        return $this->hasMany(SubTaskUser::class, 'sub_task_id');
    }

    // Recursive relationship to fetch children tasks
    public function children()
    {
        return $this->hasMany(SubTask::class, 'parent_id')->with('children');
    }


    public function files()
    {

        return $this->hasMany(SubTaskFile::class);
    }
}
