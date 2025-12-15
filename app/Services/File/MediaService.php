<?php

namespace App\Services\File;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaService
{
    public function attachBase64Media($model, $base64Data, $fileName, $collectionName)
    {
        // Clear existing media from the collection
        $model->clearMediaCollection($collectionName);

        // Add new media
        $model->addMediaFromBase64($base64Data)->usingFileName($fileName)->toMediaCollection($collectionName);

        // Save the model
        $model->save();
    }

    public function getMediaUrl($model, $collectionName)
    {
        $media = $model->getFirstMedia($collectionName);

        return $media ? $media->getUrl() : null;
    }
}
