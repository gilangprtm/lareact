<?php

namespace App\Http\Controllers\DB;

use App\Http\Controllers\Controller;
use App\Http\Requests\DB\BookRequest;
use App\Models\Book;
use App\Services\DB\Contracts\BookServiceInterface;
use App\Enums\MessageType;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class BookController extends Controller
{
    protected $pageSettings;

    public function __construct(
        private readonly BookServiceInterface $bookService
    ) {
        $this->pageSettings = [
            'title' => 'Book',
            'subtitle' => 'Manage all books in the system'
        ];
    }

    public function index(): Response
    {
        return inertia('Book/Index', [
            'datas' => $this->bookService->getAllWithRelations(),
            'page_settings' => $this->pageSettings,
            'state' => $this->getState()
        ]);
    }

    public function create(): Response
    {
        return inertia('Book/Form', [
            'data' => null,
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'POST',
                'action' => route('books.store'),
                'mode' => 'create'
            ])
        ]);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        try {
            $this->bookService->create($request->validated());
            return redirect()
                ->route('books.index')
                ->with('message', MessageType::CREATED->message('Book'));
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Book'));
        }
    }

    public function edit(Book $book): Response
    {
        return inertia('Book/Form', [
            'data' => $book->load([
                'category:id,name',
                'publisher:id,name',
                'authors:id,name',
                'images',
                'files'
            ]),
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'PUT',
                'action' => route('books.update', $book),
                'mode' => 'edit'
            ])
        ]);
    }

    public function view(Book $book): Response
    {
        return inertia('Book/Form', [
            'data' => $book->load([
                'category:id,name',
                'publisher:id,name',
                'authors:id,name',
                'images',
                'files'
            ]),
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'GET',
                'action' => route('books.edit', $book),
                'mode' => 'view'
            ])
        ]);
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        try {
            $this->bookService->update($request->validated(), $book->id);
            return redirect()
                ->route('books.index')
                ->with('message', MessageType::UPDATED->message('Book'));
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Book'));
        }
    }

    public function destroy(Book $book): RedirectResponse
    {
        if ($book->loans()->exists()) {
            return back()->with('error', 'Cannot delete book with active loans');
        }

        try {
            $this->bookService->delete($book->id);
            return redirect()
                ->route('books.index')
                ->with('message', MessageType::DELETED->message('Book'));
        } catch (\Throwable $e) {
            return back()
                ->with('error', MessageType::ERROR->message('Book'));
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
