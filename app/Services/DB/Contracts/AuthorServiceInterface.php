<?php

namespace App\Services\DB\Contracts;

use App\Models\Author;
use App\Services\DB\BaseServiceInterface;

interface AuthorServiceInterface extends BaseServiceInterface
{
    public function getAllWithBooks(int $perPage = 10): array;
}
