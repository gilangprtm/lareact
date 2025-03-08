<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DB\PublisherController as DBPublisherController;
use App\Enums\MessageType;
use App\Http\Requests\DB\PublisherRequest;
use App\Models\Publisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class PublisherController extends Controller
{
    protected array $pageSettings;

    public function __construct(
        protected DBPublisherController $dbController
    ) {
        $this->pageSettings = [
            'title' => 'Penerbit',
            'subtitle' => 'Menampilkan semua data penerbit yang tersedia di platform ini',
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
            $publishers = $this->dbController->index();

            return inertia('Publishers/Index', [
                'datas' => $publishers,
                'pageSettings' => $this->pageSettings,
                'state' => $this->getState(),
            ]);
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message('Penerbit', $e->getMessage()), 'error');
            return back();
        }
    }

    public function create(): Response
    {
        return inertia('Publishers/Form', [
            'data' => null,
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'POST',
                'action' => route('publishers.store'),
                'mode' => 'create'
            ])
        ]);
    }

    public function store(PublisherRequest $request): RedirectResponse
    {
        try {
            $this->dbController->create($request->validated());
            return redirect()
                ->route('publishers.index')
                ->with('message', MessageType::CREATED->message('Penerbit'));
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Penerbit', $e->getMessage()));
        }
    }

    public function edit(Publisher $publisher): Response
    {
        return inertia('Publishers/Form', [
            'data' => $publisher->load('books:id,title,publisher_id'),
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'PUT',
                'action' => route('publishers.update', $publisher),
                'mode' => 'edit'
            ])
        ]);
    }

    public function view(Publisher $publisher): Response
    {
        return inertia('Publishers/Form', [
            'data' => $publisher->load('books:id,title,publisher_id'),
            'pageSettings' => array_merge($this->pageSettings, [
                'method' => 'GET',
                'action' => route('publishers.edit', $publisher),
                'mode' => 'view'
            ])
        ]);
    }

    public function update(PublisherRequest $request, Publisher $publisher): RedirectResponse
    {
        try {
            $this->dbController->update($request->validated(), $publisher->id);
            return redirect()
                ->route('publishers.index')
                ->with('message', MessageType::UPDATED->message('Penerbit'));
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Penerbit', $e->getMessage()));
        }
    }

    public function destroy(Publisher $publisher): RedirectResponse
    {
        try {
            if ($publisher->books()->exists()) {
                return back()->with('error', 'Tidak dapat menghapus penerbit yang memiliki buku');
            }

            $this->dbController->delete($publisher->id);
            return redirect()
                ->route('publishers.index')
                ->with('message', MessageType::DELETED->message('Penerbit'));
        } catch (Throwable $e) {
            return back()->with('error', MessageType::ERROR->message('Penerbit', $e->getMessage()));
        }
    }
}
