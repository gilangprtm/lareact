<?php

namespace App\Services\DB\Contracts;

use App\Services\DB\BaseServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface CategoryServiceInterface extends BaseServiceInterface
{
    public function getAllWithChildren(int $perPage = 10): LengthAwarePaginator;

    public function getParentCategories(): array;

    public function generateSlug(string $name): string;
}
