<?php

namespace App\Http\Controllers\DB;

use App\Http\Controllers\Controller;
use App\Http\Requests\DB\AuthorRequest;
use App\Models\Author;
use App\Services\DB\Contracts\AuthorServiceInterface;
use App\Enums\MessageType;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class AuthorController extends Controller
{
    protected $pageSettings;

    public function __construct(
        private readonly AuthorServiceInterface $authorService
    ) {
        $this->pageSettings = [
            'title' => 'Author',
            'subtitle' => 'Manage all authors in the system'
        ];
    }

    public function index(): Response
    {
        return inertia('Author/Index', [
            'datas' => $this->authorService->getAllWithBooks(),
            'page_settings' => $this->pageSettings,
            'state' => $this->getState()
        ]);
    }

    public function create(): Response
    {
        return inertia('Author/Form', [
            'data' => null,
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'POST',
                'action' => route('authors.store'),
                'mode' => 'create'
            ])
        ]);
    }

    public function store(AuthorRequest $request): RedirectResponse
    {
        try {
            $this->authorService->create($request->validated());
            return redirect()
                ->route('authors.index')
                ->with('message', MessageType::CREATED->message('Author'));
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Author'));
        }
    }

    public function edit(Author $author): Response
    {
        return inertia('Author/Form', [
            'data' => $author->load('books:id,title'),
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'PUT',
                'action' => route('authors.update', $author),
                'mode' => 'edit'
            ])
        ]);
    }

    public function view(Author $author): Response
    {
        return inertia('Author/Form', [
            'data' => $author->load('books:id,title'),
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'GET',
                'action' => route('authors.edit', $author),
                'mode' => 'view'
            ])
        ]);
    }

    public function update(AuthorRequest $request, Author $author): RedirectResponse
    {
        try {
            $this->authorService->update($request->validated(), $author->id);
            return redirect()
                ->route('authors.index')
                ->with('message', MessageType::UPDATED->message('Author'));
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Author'));
        }
    }

    public function destroy(Author $author): RedirectResponse
    {
        if ($author->books()->exists()) {
            return back()->with('error', 'Cannot delete author with books');
        }

        try {
            $this->authorService->delete($author->id);
            return redirect()
                ->route('authors.index')
                ->with('message', MessageType::DELETED->message('Author'));
        } catch (\Throwable $e) {
            return back()
                ->with('error', MessageType::ERROR->message('Author'));
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
