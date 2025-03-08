<?php

namespace App\Http\Controllers\DB;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\DB\Providers\BookService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BookController extends Controller
{
    public function __construct(
        protected BookService $bookService
    ) {}

    public function index(): LengthAwarePaginator
    {
        return $this->bookService->getAllWithRelations();
    }

    public function find(int $id): Book
    {
        return $this->bookService->find($id);
    }

    public function create(array $data): Book
    {
        return $this->bookService->create($data);
    }

    public function update(array $data, int $id): Book
    {
        $book = $this->bookService->find($id);
        return $this->bookService->update($data, $book);
    }

    public function delete(int $id): bool
    {
        $book = $this->bookService->find($id);
        return $this->bookService->delete($book);
    }

    public function getAll(): Collection
    {
        return $this->bookService->getAll();
    }
}
