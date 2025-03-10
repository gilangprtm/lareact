<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DB\CategoryController as DBCategoryController;
use App\Enums\MessageType;
use App\Http\Requests\DB\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class CategoryController extends Controller
{
    protected array $pageSettings;

    public function __construct(
        protected DBCategoryController $dbController
    ) {
        $this->pageSettings = [
            'title' => 'Kategori',
            'subtitle' => 'Menampilkan semua data kategori yang tersedia di platform ini',
        ];
    }

    protected function getState(): array
    {
        $defaultState = [
            'search' => '',
            'page' => request()->page ?? 1,
            'load' => request()->load ?? 10,
        ];

        return array_merge(
            $defaultState,
            request()->only(array_keys($defaultState))
        );
    }

    public function index(): Response
    {
        try {
            $categories = $this->dbController->index();

            return inertia('Categories/Index', [
                'datas' => $categories,
                'pageSettings' => $this->pageSettings,
                'state' => $this->getState(),
            ]);
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Kategori', $e->getMessage()));
        }
    }

    public function create(): Response
    {
        return inertia('Categories/Form', [
            'data' => null,
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'POST',
                'action' => route('categories.store'),
                'mode' => 'create'
            ])
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        try {
            $this->dbController->create($request->validated());
            return redirect()
                ->route('categories.index')
                ->with('message', MessageType::CREATED->message('Kategori'));
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Kategori', $e->getMessage()));
        }
    }

    public function edit(Category $category): Response
    {
        return inertia('Categories/Form', [
            'data' => $category->load(['parent:id,name', 'children:id,name']),
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'PUT',
                'action' => route('categories.update', $category),
                'mode' => 'edit'
            ])
        ]);
    }

    public function view(Category $category): Response
    {
        return inertia('Categories/Form', [
            'data' => $category->load(['parent:id,name', 'children:id,name']),
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'GET',
                'action' => route('categories.edit', $category),
                'mode' => 'view'
            ])
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $this->dbController->update($request->validated(), $category->id);
            return redirect()
                ->route('categories.index')
                ->with('message', MessageType::UPDATED->message('Kategori'));
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Kategori', $e->getMessage()));
        }
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            if ($category->children()->exists()) {
                return back()->with('error', 'Tidak dapat menghapus kategori yang memiliki sub-kategori');
            }

            if ($category->books()->exists()) {
                return back()->with('error', 'Tidak dapat menghapus kategori yang memiliki buku');
            }

            $this->dbController->delete($category->id);
            return redirect()
                ->route('categories.index')
                ->with('message', MessageType::DELETED->message('Kategori'));
        } catch (Throwable $e) {
            return back()->with('error', MessageType::ERROR->message('Kategori', $e->getMessage()));
        }
    }
}
