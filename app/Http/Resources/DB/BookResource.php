<?php

namespace App\Http\Resources\DB;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'publish_date' => $this->publish_date?->format('Y-m-d'),
            'pages' => $this->pages,
            'price' => $this->price,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'language' => $this->language,

            // Relations with their own resources
            'category' => new CategoryResource($this->whenLoaded('category')),
            'publisher' => new PublisherResource($this->whenLoaded('publisher')),
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),

            // File relations
            'images' => BookImageResource::collection($this->whenLoaded('images')),
            'files' => BookFileResource::collection($this->whenLoaded('files')),

            // Computed properties
            'can_delete' => !$this->hasActiveLoans(),
            'cover_image' => $this->images->where('image_type', 'cover')->first()?->image_path,

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
