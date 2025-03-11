<?php

namespace App\Services\DB\Providers;

use App\Models\Category;
use App\Services\DB\Contracts\CategoryServiceInterface;
use App\Services\DB\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CategoryService extends BaseService implements CategoryServiceInterface
{
    protected function getModel(): Model
    {
        return new Category();
    }

    protected function getFilterableFields(): array
    {
        return ['search'];
    }

    protected function beforeCreate(array &$data): void
    {
        $data['slug'] = $this->generateSlug($data['name']);
    }

    protected function afterCreate(Model $model, array $data): void
    {
        $model->load(['parent:id,name', 'children:id,name']);
    }

    protected function beforeUpdate(array &$data, $id): void
    {
        if (isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
    }

    protected function afterUpdate(Model $model, array $data): void
    {
        $model->load(['parent:id,name', 'children:id,name']);
    }

    public function getAllWithChildren(int $perPage = 10): array
    {
        return $this->getPaginated(
            relations: ['children:id,name,slug,parent_id', 'books:id,title,category_id']
        );
    }

    public function getParentCategories(): array
    {
        return $this->model->select(['id', 'name', 'slug'])
            ->whereNull('parent_id')
            ->with(['children:id,name,slug,parent_id'])
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = $this->model->where('slug', 'LIKE', "{$slug}%")->count();
        return $count > 0 ? "{$slug}-{$count}" : $slug;
    }

    public function create(array $data)
    {
        try {
            $this->beginTransaction();

            $category = parent::create($data);

            $this->commit();
            return $category->load(['parent:id,name', 'children:id,name']);
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function update(array $data, $id)
    {
        try {
            $this->beginTransaction();

            $category = parent::update($data, $id);

            $this->commit();
            return $category->fresh(['parent:id,name', 'children:id,name']);
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $this->beginTransaction();

            $result = parent::delete($id);

            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
}
