<?php

namespace App\Services\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HandlesFileUploads
{
    protected function uploadFile(UploadedFile $file, string $path): string
    {
        return $file->store($path, 'public');
    }

    protected function removeFile(?string $path): bool
    {
        if (!$path) {
            return false;
        }
        return Storage::disk('public')->delete($path);
    }

    protected function getFileInfo(UploadedFile $file): array
    {
        return [
            'file_type' => $file->getClientOriginalExtension(),
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ];
    }
}
