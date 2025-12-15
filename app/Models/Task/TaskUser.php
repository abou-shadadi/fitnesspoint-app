<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskUser extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function task()
    {

        return $this->belongsTo(Task::class);
    }

    public function user()
    {

        return $this->belongsTo(\App\Models\User::class);
    }
}
