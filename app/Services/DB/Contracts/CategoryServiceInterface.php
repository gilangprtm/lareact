<?php

namespace App\Services\DB\Contracts;

use App\Services\DB\BaseServiceInterface;

interface CategoryServiceInterface extends BaseServiceInterface
{
    public function getAllWithChildren(int $perPage = 10): array;

    public function getParentCategories(): array;

    public function generateSlug(string $name): string;
}
