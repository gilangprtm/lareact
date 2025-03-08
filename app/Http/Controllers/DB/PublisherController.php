<?php

namespace App\Http\Controllers\DB;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use App\Services\DB\Providers\PublisherService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PublisherController extends Controller
{
    public function __construct(
        protected PublisherService $publisherService
    ) {}

    public function index(): LengthAwarePaginator
    {
        return $this->publisherService->getAllWithBooks();
    }

    public function find(int $id): Publisher
    {
        return $this->publisherService->find($id);
    }

    public function create(array $data): Publisher
    {
        return $this->publisherService->create($data);
    }

    public function update(array $data, int $id): Publisher
    {
        $publisher = $this->publisherService->find($id);
        return $this->publisherService->update($data, $publisher);
    }

    public function delete(int $id): bool
    {
        $publisher = $this->publisherService->find($id);
        return $this->publisherService->delete($publisher);
    }

    public function getAll(): Collection
    {
        return $this->publisherService->getAll();
    }
}
