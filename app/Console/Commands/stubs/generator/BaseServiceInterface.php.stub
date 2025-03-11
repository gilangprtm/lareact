<?php

namespace App\Services\DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

interface BaseServiceInterface
{
    // Basic CRUD
    public function getAll(array $columns = ['*']);

    public function create(array $data);

    public function update(array $data, $id);

    public function delete($id);

    public function find($id);

    // File Handling
    public function handleFileUpload(UploadedFile $file, string $path): string;

    public function handleImageUpload(UploadedFile $image, string $path): string;

    public function deleteFile(string $path): bool;

    // Relationships
    public function sync(Model $model, string $relation, array $ids): void;

    public function attach(Model $model, string $relation, array $ids): void;

    public function detach(Model $model, string $relation, array $ids): void;
}
