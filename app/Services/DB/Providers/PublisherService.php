<?php

namespace App\Services\DB\Providers;

use App\Models\Publisher;
use App\Services\DB\Contracts\PublisherServiceInterface;
use App\Services\DB\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class PublisherService extends BaseService implements PublisherServiceInterface
{
    protected function getModel(): Model
    {
        return new Publisher();
    }

    protected function beforeCreate(array &$data): void
    {
        if (isset($data['logo'])) {
            $data['logo_path'] = $this->handleImageUpload($data['logo'], 'publishers');
            unset($data['logo']);
        }
    }

    protected function afterCreate(Model $model, array $data): void
    {
        $model->load('books:id,title,publisher_id');
    }

    protected function beforeUpdate(array &$data, $id): void
    {
        if (isset($data['logo'])) {
            $publisher = $this->find($id);
            if ($publisher->logo_path) {
                $this->deleteFile($publisher->logo_path);
            }
            $data['logo_path'] = $this->handleImageUpload($data['logo'], 'publishers');
            unset($data['logo']);
        }
    }

    protected function afterUpdate(Model $model, array $data): void
    {
        $model->load('books:id,title,publisher_id');
    }

    protected function beforeDelete($id): void
    {
        $publisher = $this->find($id);
        if ($publisher->logo_path) {
            $this->deleteFile($publisher->logo_path);
        }
    }

    public function getAllWithBooks(int $perPage = 10): LengthAwarePaginator
    {
        return $this->getPaginated(
            relations: ['books:id,title,publisher_id']
        );
    }
}
