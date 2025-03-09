<?php

namespace App\Http\Resources\API;

use App\DTO\CategoryDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Menggunakan DTO untuk transformasi dan dokumentasi
        return CategoryDto::fromModel($this->resource)->toArray();
    }
}
