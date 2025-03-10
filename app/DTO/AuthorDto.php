<?php

namespace App\DTO;

use App\Models\Author;

/**
 * @OA\Schema(
 *     schema="Author",
 *     title="Author DTO",
 *     description="Data Transfer Object for Author"
 * )
 */
class AuthorDto extends BaseDto
{
    /**
     * @OA\Property(type="int", example=42)
     */
    public int $id;

    /**
     * @OA\Property(type="string", example="John Doe", nullable=true)
     */
    public ?string $name = null;

    /**
     * @OA\Property(type="string", example="john@example.com", format="email", nullable=true)
     */
    public ?string $email = null;

    /**
     * @OA\Property(type="string", example="Author of several bestselling novels.", nullable=true)
     */
    public ?string $bio = null;

    /**
     * @OA\Property(type="string", example="authors/abc123.jpg", nullable=true)
     */
    public ?string $photo_path = null;

    /**
     * @OA\Property(type="string", example="http://example.com/storage/authors/abc123.jpg", nullable=true)
     */
    public ?string $photo_url = null;

    /**
     * @OA\Property(type="string", example="2023-01-15 14:30:45", format="date-time", nullable=true)
     */
    public ?string $created_at = null;

    /**
     * @OA\Property(type="string", example="2023-02-20 09:15:30", format="date-time", nullable=true)
     */
    public ?string $updated_at = null;

    /**
     * Create a DTO from a Author model
     *
     * @param Author $model The model instance
     * @return self
     */
    public static function fromModel(Author $model): self
    {
        $dto = self::fromSource($model);

        // Handle date fields
        $dto->created_at = $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : null;
        $dto->updated_at = $model->updated_at ? $model->updated_at->format('Y-m-d H:i:s') : null;

        // Generate photo URL if photo_path exists
        $dto->photo_url = $model->photo_path ? asset('storage/' . $model->photo_path) : null;

        return $dto;
    }
}
