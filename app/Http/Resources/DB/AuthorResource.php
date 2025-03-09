<?php

namespace App\Http\Resources\DB;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="AuthorResource",
 *     title="Author Resource",
 *     description="Author data response",
 *     type="object"
 * )
 */
class AuthorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @OA\Property(property="id", type="integer", example=1)
     * @OA\Property(property="name", type="string", example="John Doe")
     * @OA\Property(property="slug", type="string", example="john-doe")
     * @OA\Property(property="email", type="string", format="email", example="johndoe@example.com")
     * @OA\Property(property="bio", type="string", nullable=true, example="A famous author.")
     * @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1980-05-15")
     * @OA\Property(property="death_date", type="string", format="date", nullable=true, example=null)
     * @OA\Property(property="nationality", type="string", example="American")
     * @OA\Property(property="website", type="string", format="url", nullable=true, example="https://johndoe.com")
     * @OA\Property(property="is_active", type="boolean", example=true)
     * @OA\Property(property="books_count", type="integer", example=5)
     * @OA\Property(property="avatar", type="string", format="url", nullable=true, example="https://example.com/avatar.jpg")
     * @OA\Property(property="can_delete", type="boolean", example=false)
     * @OA\Property(property="age", type="integer", example=43)
     * @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-09 14:25:36")
     * @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-10 08:15:22")
     *
     * @param Request $request
     * @return array
     */
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
