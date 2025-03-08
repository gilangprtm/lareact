<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DB\AuthorController as DBAuthorController;
use App\Enums\MessageType;
use App\Http\Requests\DB\AuthorRequest;
use App\Models\Author;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class AuthorController extends Controller
{
    protected array $pageSettings;

    public function __construct(
        protected DBAuthorController $dbController
    ) {
        $this->pageSettings = [
            'title' => 'Penulis',
            'subtitle' => 'Menampilkan semua data penulis yang tersedia di platform ini',
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
            $authors = $this->dbController->index();

            return inertia('Authors/Index', [
                'datas' => $authors,
                'pageSettings' => $this->pageSettings,
                'state' => $this->getState(),
            ]);
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message('Penulis', $e->getMessage()), 'error');
            return back();
        }
    }

    public function create(): Response
    {
        return inertia('Authors/Form', [
            'data' => null,
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'POST',
                'action' => route('authors.store'),
                'mode' => 'create'
            ])
        ]);
    }

    public function store(AuthorRequest $request): RedirectResponse
    {
        try {
            $this->dbController->create($request->validated());
            return redirect()
                ->route('authors.index')
                ->with('message', MessageType::CREATED->message('Penulis'));
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Penulis', $e->getMessage()));
        }
    }

    public function edit(Author $author): Response
    {
        return inertia('Authors/Form', [
            'data' => $author->load('books:id,title'),
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'PUT',
                'action' => route('authors.update', $author),
                'mode' => 'edit'
            ])
        ]);
    }

    public function view(Author $author): Response
    {
        return inertia('Authors/Form', [
            'data' => $author->load('books:id,title'),
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'GET',
                'action' => route('authors.edit', $author),
                'mode' => 'view'
            ])
        ]);
    }

    public function update(AuthorRequest $request, Author $author): RedirectResponse
    {
        try {
            $this->dbController->update($request->validated(), $author->id);
            return redirect()
                ->route('authors.index')
                ->with('message', MessageType::UPDATED->message('Penulis'));
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Penulis', $e->getMessage()));
        }
    }

    public function destroy(Author $author): RedirectResponse
    {
        try {
            if ($author->books()->exists()) {
                return back()->with('error', 'Tidak dapat menghapus penulis yang memiliki buku');
            }

            $this->dbController->delete($author->id);
            return redirect()
                ->route('authors.index')
                ->with('message', MessageType::DELETED->message('Penulis'));
        } catch (Throwable $e) {
            return back()->with('error', MessageType::ERROR->message('Penulis', $e->getMessage()));
        }
    }
}
