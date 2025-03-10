<?php

namespace App\Http\Resources\API;

use App\DTO\AuthorDto;
use App\Http\Resources\Base\BaseAuthorResource;

class AuthorResource extends BaseAuthorResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // API-specific transformation
        return array_merge($this->getBaseAttributes(), $this->getApiAttributes(), [
            'photo_url' => $this->resource->photo_path ? asset('storage/' . $this->resource->photo_path) : null,
            'links' => [
                'self' => route('api.authors.show', $this->id),
                'update' => route('api.authors.update', $this->id),
                'delete' => route('api.authors.destroy', $this->id),
            ]
        ]);
    }
}
