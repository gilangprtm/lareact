<?php

namespace App\Http\Controllers\DB;

use App\Http\Controllers\Controller;
use App\Http\Requests\DB\CategoryRequest;
use App\Models\Category;
use App\Services\DB\Contracts\CategoryServiceInterface;
use App\Enums\MessageType;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class CategoryController extends Controller
{
    protected $pageSettings;

    public function __construct(
        private readonly CategoryServiceInterface $categoryService
    ) {
        $this->pageSettings = [
            'title' => 'Category',
            'subtitle' => 'Manage all categories in the system'
        ];
    }

    public function index(): Response
    {
        return inertia('Category/Index', [
            'datas' => $this->categoryService->getAllWithChildren(),
            'page_settings' => $this->pageSettings,
            'state' => $this->getState()
        ]);
    }

    public function create(): Response
    {
        return inertia('Category/Form', [
            'data' => null,
            'parents' => $this->categoryService->getParentCategories(),
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'POST',
                'action' => route('categories.store'),
                'mode' => 'create'
            ])
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        try {
            $this->categoryService->create($request->validated());
            return redirect()
                ->route('categories.index')
                ->with('message', MessageType::CREATED->message('Category'));
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Category'));
        }
    }

    public function edit(Category $category): Response
    {
        return inertia('Category/Form', [
            'data' => $category->load(['parent:id,name', 'children:id,name']),
            'parents' => $this->categoryService->getParentCategories(),
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'PUT',
                'action' => route('categories.update', $category),
                'mode' => 'edit'
            ])
        ]);
    }

    public function view(Category $category): Response
    {
        return inertia('Category/Form', [
            'data' => $category->load(['parent:id,name', 'children:id,name']),
            'parents' => $this->categoryService->getParentCategories(),
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'GET',
                'action' => route('categories.edit', $category),
                'mode' => 'view'
            ])
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $this->categoryService->update($request->validated(), $category->id);
            return redirect()
                ->route('categories.index')
                ->with('message', MessageType::UPDATED->message('Category'));
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Category'));
        }
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->books()->exists()) {
            return back()->with('error', 'Cannot delete category with books');
        }

        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with sub-categories');
        }

        try {
            $this->categoryService->delete($category->id);
            return redirect()
                ->route('categories.index')
                ->with('message', MessageType::DELETED->message('Category'));
        } catch (\Throwable $e) {
            return back()
                ->with('error', MessageType::ERROR->message('Category'));
        }
    }

    protected function getState(): array
    {
        $defaultState = [
            // Filter & Search
            'search' => request('search', ''),

            // Sorting
            'field' => request('field', 'id'),
            'direction' => request('direction', 'desc'),

            // Pagination
            'page' => request('page', 1),
            'load' => request('load', 10),
        ];

        return array_merge(
            $defaultState,
            request()->only(array_keys($defaultState))
        );
    }
}
