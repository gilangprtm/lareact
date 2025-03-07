<?php

namespace App\Http\Resources\DB;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'image_path' => $this->image_path,
            'image_type' => $this->image_type,
            'caption' => $this->caption,
            'order' => $this->order,
            'is_primary' => $this->is_primary,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'dimensions' => [
                'width' => $this->width,
                'height' => $this->height
            ],

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
