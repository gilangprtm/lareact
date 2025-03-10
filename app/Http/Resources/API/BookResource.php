<?php

namespace App\Http\Resources\API;

use App\DTO\BookDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Pastikan relasi dimuat sebelum transformasi
        $this->resource->loadMissing([
            'category:id,name,slug',
            'publisher:id,name',
            'authors:id,name',
            'images',
            'files'
        ]);

        // Menggunakan DTO untuk transformasi dan dokumentasi
        $bookDto = BookDto::fromModel($this->resource);

        return $bookDto->toArray();
    }
}
