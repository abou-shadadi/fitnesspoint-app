<?php

namespace App\Services\File;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Base64Service
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function processBase64File($model, $base64String, $columnName = 'files', $isUpdate = false)
    {

        $fileData = $this->sanitizeBase64($base64String);

        if (!empty($fileData) && $this->isBase64($fileData)) {
            $fileExtension = $this->getFileExtensionFromBase64($base64String);
            $fileNameWithExtension = 'File_' . time() . '.' . $fileExtension;

            if ($isUpdate) {
                // Update an existing file
                $this->mediaService->attachBase64Media($model, $fileData, $fileNameWithExtension, $columnName);
            } else {
                // Create a new file
                $this->mediaService->attachBase64Media($model, $fileData, $fileNameWithExtension, $columnName);
            }
        }
    }

    protected function sanitizeBase64($base64Data)
    {
        $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $base64Data);
        $base64Data = str_replace([' ', "\t", "\r", "\n"], '', $base64Data);

        if ($this->isBase64($base64Data)) {
            return $base64Data;
        } else {
            return null;
        }
    }

    protected function isBase64($string)
    {
        $validator = Validator::make(['data' => $string], ['data' => 'base64file']);
        return !$validator->fails();
    }

    protected function getFileExtensionFromBase64($base64String)
    {
        // Decode the base64 string
        $decodedData = base64_decode($base64String);

        // Create a file info resource
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        // Get MIME type
        $mime = finfo_buffer($finfo, $decodedData);

        finfo_close($finfo);

        // Extract the file extension from the MIME type
        $mimeParts = explode('/', $mime);

        // Check for PDF subtype
        if (count($mimeParts) === 2 && strtolower($mimeParts[1]) === 'pdf') {
            return 'pdf';
        }

        // Use the last part of the array as the file extension
        $fileExtension = end($mimeParts);

        // Default to a generic extension if unable to determine
        return $fileExtension ?: 'png';
    }

}
