<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DB\BookController as DBBookController;
use App\Enums\MessageType;
use App\Http\Requests\DB\BookRequest;
use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class BookController extends Controller
{
    protected array $pageSettings;

    public function __construct(
        protected DBBookController $dbController
    ) {
        $this->pageSettings = [
            'title' => 'Buku',
            'subtitle' => 'Menampilkan semua data buku yang tersedia di platform ini',
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
            $books = $this->dbController->index();

            return inertia('Books/Index', [
                'datas' => $books,
                'pageSettings' => $this->pageSettings,
                'state' => $this->getState(),
            ]);
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Buku', $e->getMessage()));
        }
    }

    public function create(): Response
    {
        return inertia('Books/Form', [
            'data' => null,
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'POST',
                'action' => route('books.store'),
                'mode' => 'create'
            ])
        ]);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        try {
            $this->dbController->create($request->validated());
            return redirect()
                ->route('books.index')
                ->with('message', MessageType::CREATED->message('Buku'));
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Buku', $e->getMessage()));
        }
    }

    public function edit(Book $book): Response
    {
        return inertia('Books/Form', [
            'data' => $book->load([
                'category:id,name',
                'publisher:id,name',
                'authors:id,name',
                'images',
                'files'
            ]),
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'PUT',
                'action' => route('books.update', $book),
                'mode' => 'edit'
            ])
        ]);
    }

    public function view(Book $book): Response
    {
        return inertia('Books/Form', [
            'data' => $book->load([
                'category:id,name',
                'publisher:id,name',
                'authors:id,name',
                'images',
                'files'
            ]),
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'GET',
                'action' => route('books.edit', $book),
                'mode' => 'view'
            ])
        ]);
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        try {
            $this->dbController->update($request->validated(), $book->id);
            return redirect()
                ->route('books.index')
                ->with('message', MessageType::UPDATED->message('Buku'));
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Buku', $e->getMessage()));
        }
    }

    public function destroy(Book $book): RedirectResponse
    {
        try {
            if ($book->loans()->exists()) {
                return back()->with('error', 'Tidak dapat menghapus buku yang sedang dipinjam');
            }

            $this->dbController->delete($book->id);
            return redirect()
                ->route('books.index')
                ->with('message', MessageType::DELETED->message('Buku'));
        } catch (Throwable $e) {
            return back()->with('error', MessageType::ERROR->message('Buku', $e->getMessage()));
        }
    }
}
