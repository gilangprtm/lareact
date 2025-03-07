<?php

namespace App\Services\DB\Contracts;

use App\Models\Publisher;
use App\Services\DB\BaseServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface PublisherServiceInterface extends BaseServiceInterface
{
    public function getAllWithBooks(int $perPage = 10): LengthAwarePaginator;
}
