<?php

namespace App\Http\Controllers\DB;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\DB\Providers\CategoryService;
use Illuminate\Database\Eloquent\Collection;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Get all categories with pagination
     * 
     * @return array
     */
    public function index(): array
    {
        return $this->categoryService->getAllWithChildren();
    }

    public function find(int $id): Category
    {
        return $this->categoryService->find($id);
    }

    public function create(array $data): Category
    {
        return $this->categoryService->create($data);
    }

    public function update(array $data, int $id): Category
    {
        $category = $this->categoryService->find($id);
        return $this->categoryService->update($category, $data);
    }

    public function delete(int $id): bool
    {
        $category = $this->categoryService->find($id);
        return $this->categoryService->delete($category);
    }

    public function getAll(): Collection
    {
        return $this->categoryService->getAll();
    }
}
