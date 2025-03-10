<?php

namespace App\Http\Resources\DB;

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
        // Web-specific transformation
        return array_merge($this->getBaseAttributes(), $this->getWebAttributes(), [
            'photo_url' => $this->resource->photo_path ? asset('storage/' . $this->resource->photo_path) : null,
            'edit_url' => route('authors.edit', $this->id),
            'delete_url' => route('authors.destroy', $this->id),
            'back_url' => route('authors.index'),
        ]);
    }
}
