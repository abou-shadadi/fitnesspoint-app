<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TaskFile extends Model implements HasMedia
{
    protected $guarded = [];
    use HasFactory, InteractsWithMedia;



    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile();
    }

    public function getFileAttribute()
    {
        // Get the first media item from the 'featured_image' collection
        $media = $this->getFirstMedia('file');

        // Return the URL of the media item if it exists, else return a default URL
        return $media ? $media->getUrl() : null;
    }


    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
