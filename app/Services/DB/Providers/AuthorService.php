<?php

namespace App\Services\DB\Providers;

use App\Models\Author;
use App\Services\DB\Contracts\AuthorServiceInterface;
use App\Services\DB\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class AuthorService extends BaseService implements AuthorServiceInterface
{
    protected function getModel(): Model
    {
        return new Author();
    }

    protected function getFilterableFields(): array
    {
        return ['search'];
    }

    public function getAllWithBooks(int $perPage = 10): LengthAwarePaginator
    {
        return $this->getPaginated(
            relations: ['books:id,title'],
            counts: ['books']
        );
    }
}
