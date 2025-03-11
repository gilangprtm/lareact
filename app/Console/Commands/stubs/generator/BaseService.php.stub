<?php

namespace App\Services\DB;

use App\Services\DB\BaseServiceInterface;
use App\Services\Traits\HandlesFileUploads;
use App\Services\Traits\WithPagination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

abstract class BaseService implements BaseServiceInterface
{
    use HandlesFileUploads;

    use WithPagination;

    protected $model;

    public function __construct()
    {
        $this->model = $this->getModel();
    }

    abstract protected function getModel(): Model;
    abstract protected function getFilterableFields(): array;
    protected function beforeCreate(array &$data): void {}
    protected function afterCreate(Model $model, array $data): void {}

    protected function beforeUpdate(array &$data, $id): void {}
    protected function afterUpdate(Model $model, array $data): void {}

    protected function beforeDelete($id): void {}
    protected function afterDelete($id): void {}

    protected function getPaginated(array $relations = [], array $counts = []): array
    {
        $paginator = $this->getModel()::query()
            ->with($relations)
            ->withCount($counts)
            ->filter(request()->only($this->getFilterableFields()))
            ->sorting(request()->only(['field', 'direction']))
            ->paginate(request('load', 10))
            ->appends(request()->query());

        return $this->formatPagination($paginator);
    }

    public function getAll(array $columns = ['*'])
    {
        return $this->model->get($columns);
    }

    public function create(array $data)
    {
        try {
            $this->beginTransaction();

            $this->beforeCreate($data);
            $model = $this->model->create($data);
            $this->afterCreate($model, $data);

            $this->commit();
            return $model;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function update(array $data, $id)
    {
        try {
            $this->beginTransaction();

            $this->beforeUpdate($data, $id);
            $model = $this->find($id);
            $model->update($data);
            $this->afterUpdate($model, $data);

            $this->commit();
            return $model;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $this->beginTransaction();

            $this->beforeDelete($id);
            $result = $this->model->destroy($id);
            $this->afterDelete($id);

            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function handleFileUpload(UploadedFile $file, string $path): string
    {
        return $this->uploadFile($file, $path);
    }

    public function handleImageUpload(UploadedFile $image, string $path): string
    {
        return $this->uploadFile($image, $path);
    }

    public function deleteFile(string $path): bool
    {
        return $this->removeFile($path);
    }

    public function sync(Model $model, string $relation, array $ids): void
    {
        $model->{$relation}()->sync($ids);
    }

    public function attach(Model $model, string $relation, array $ids): void
    {
        $model->{$relation}()->attach($ids);
    }

    public function detach(Model $model, string $relation, array $ids): void
    {
        $model->{$relation}()->detach($ids);
    }

    protected function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    protected function commit(): void
    {
        DB::commit();
    }

    protected function rollBack(): void
    {
        DB::rollBack();
    }
}
