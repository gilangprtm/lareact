<?php

namespace App\Http\Resources\Base;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseAuthorResource extends JsonResource
{
    /**
     * Get the base attributes common to both API and Web resources.
     *
     * @return array
     */
    protected function getBaseAttributes(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'bio' => $this->bio,
            'photo_path' => $this->photo_path,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the web-specific attributes.
     *
     * @return array
     */
    protected function getWebAttributes(): array
    {
        return [
            // Add web-specific attributes here
            // Example: 'edit_url' => route('Authors.edit', $this->id),
        ];
    }

    /**
     * Get the API-specific attributes.
     *
     * @return array
     */
    protected function getApiAttributes(): array
    {
        return [
            // Add API-specific attributes here
            // Example: 'links' => [
            //     'self' => route('api.Authors.show', $this->id),
            // ],
        ];
    }
}
