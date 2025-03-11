<?php

namespace App\Services\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait WithPagination
{
    /**
     * Format paginator for Inertia response
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    protected function formatPagination(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'first_page_url' => $paginator->firstItem(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastItem(),
                'last_page_url' => $paginator->lastItem(),
                'links' => $paginator->linkCollection()->toArray(),
                'next_page_url' => $paginator->nextPageUrl(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
