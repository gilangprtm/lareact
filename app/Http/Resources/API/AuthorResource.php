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
        return AuthorDto::fromAuthor($this->resource)->toArray();
    }
}
