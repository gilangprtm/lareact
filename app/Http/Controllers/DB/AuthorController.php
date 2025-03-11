<?php

namespace App\Http\Controllers\DB;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Services\DB\Providers\AuthorService;
use Illuminate\Database\Eloquent\Collection;

class AuthorController extends Controller
{
    public function __construct(
        protected AuthorService $authorService
    ) {}

    public function index(): array
    {
        return $this->authorService->getAllWithBooks();
    }

    public function find(int $id): Author
    {
        return $this->authorService->find($id);
    }

    public function create(array $data): Author
    {
        return $this->authorService->create($data);
    }

    public function update(array $data, int $id): Author
    {
        $author = $this->authorService->find($id);
        return $this->authorService->update($data, $author);
    }

    public function delete(int $id): bool
    {
        $author = $this->authorService->find($id);
        return $this->authorService->delete($author);
    }

    public function getAll(): Collection
    {
        return $this->authorService->getAll();
    }
}
