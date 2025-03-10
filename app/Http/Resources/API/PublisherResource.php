<?php

namespace App\Http\Resources\API;

use App\DTO\PublisherDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Pastikan relasi dimuat sebelum transformasi
        $this->resource->loadMissing(['books:id,title,publisher_id']);

        // Menggunakan DTO untuk transformasi dan dokumentasi
        $publisherDto = PublisherDto::fromModel($this->resource);

        // Memastikan logo_url dibuat dengan benar
        if ($this->resource->logo_path) {
            $publisherDto->logo_url = asset('storage/' . $this->resource->logo_path);
        }

        return $publisherDto->toArray();
    }
}
