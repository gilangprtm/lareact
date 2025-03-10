<?php

namespace App\Http\Resources\API;

use App\DTO\AuthorDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Menggunakan DTO untuk transformasi dan dokumentasi
        $authorDto = AuthorDto::fromModel($this->resource);

        // Memastikan photo_url dibuat dengan benar
        if ($this->resource->photo_path) {
            $authorDto->photo_url = asset('storage/' . $this->resource->photo_path);
        }

        return $authorDto->toArray();
    }
}
