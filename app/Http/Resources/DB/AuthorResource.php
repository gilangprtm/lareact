<?php

namespace App\Http\Resources\DB;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'bio' => $this->bio,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'death_date' => $this->death_date?->format('Y-m-d'),
            'nationality' => $this->nationality,
            'website' => $this->website,
            'is_active' => $this->is_active,

            // Relations
            'books_count' => $this->whenCounted('books'),
            'avatar' => $this->avatar?->image_path,

            // Computed properties
            'can_delete' => $this->books->isEmpty(),
            'age' => $this->calculateAge(),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
