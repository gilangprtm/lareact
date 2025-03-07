<?php

namespace App\Http\Controllers\DB;

use App\Http\Controllers\Controller;
use App\Http\Requests\DB\PublisherRequest;
use App\Models\Publisher;
use App\Services\DB\Contracts\PublisherServiceInterface;
use App\Enums\MessageType;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PublisherController extends Controller
{
    protected $pageSettings;

    public function __construct(
        private readonly PublisherServiceInterface $publisherService
    ) {
        $this->pageSettings = [
            'title' => 'Publisher',
            'subtitle' => 'Manage all publishers in the system'
        ];
    }

    public function index(): Response
    {
        return inertia('Publisher/Index', [
            'datas' => $this->publisherService->getAllWithBooks(),
            'page_settings' => $this->pageSettings,
            'state' => $this->getState()
        ]);
    }

    public function create(): Response
    {
        return inertia('Publisher/Form', [
            'data' => null,
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'POST',
                'action' => route('publishers.store'),
                'mode' => 'create'
            ])
        ]);
    }

    public function store(PublisherRequest $request): RedirectResponse
    {
        try {
            $this->publisherService->create($request->validated());
            return redirect()
                ->route('publishers.index')
                ->with('message', MessageType::CREATED->message('Publisher'));
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Publisher'));
        }
    }

    public function edit(Publisher $publisher): Response
    {
        return inertia('Publisher/Form', [
            'data' => $publisher->load('books:id,title,publisher_id'),
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'PUT',
                'action' => route('publishers.update', $publisher),
                'mode' => 'edit'
            ])
        ]);
    }

    public function view(Publisher $publisher): Response
    {
        return inertia('Publisher/Form', [
            'data' => $publisher->load('books:id,title,publisher_id'),
            'page_settings' => array_merge($this->pageSettings, [
                'method' => 'GET',
                'action' => route('publishers.edit', $publisher),
                'mode' => 'view'
            ])
        ]);
    }

    public function update(PublisherRequest $request, Publisher $publisher): RedirectResponse
    {
        try {
            $this->publisherService->update($request->validated(), $publisher->id);
            return redirect()
                ->route('publishers.index')
                ->with('message', MessageType::UPDATED->message('Publisher'));
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', MessageType::ERROR->message('Publisher'));
        }
    }

    public function destroy(Publisher $publisher): RedirectResponse
    {
        if ($publisher->books()->exists()) {
            return back()->with('error', 'Cannot delete publisher with books');
        }

        try {
            $this->publisherService->delete($publisher->id);
            return redirect()
                ->route('publishers.index')
                ->with('message', MessageType::DELETED->message('Publisher'));
        } catch (\Throwable $e) {
            return back()
                ->with('error', MessageType::ERROR->message('Publisher'));
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
