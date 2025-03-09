<?php

namespace App\DTO;

use App\Models\Author;

/**
 * @OA\Schema(
 *     schema="Author",
 *     title="Author DTO",
 *     description="Data Transfer Object untuk Author"
 * )
 */
class AuthorDto extends BaseDto
{
    /**
     * @OA\Property(type="integer", example=1)
     */
    public ?int $id = null;

    /**
     * @OA\Property(type="string", example="John Doe")
     */
    public string $name;

    /**
     * @OA\Property(type="string", example="john-doe")
     */
    public string $slug;

    /**
     * @OA\Property(type="string", format="email", example="johndoe@example.com", nullable=true)
     */
    public ?string $email = null;

    /**
     * @OA\Property(type="string", example="A famous author.", nullable=true)
     */
    public ?string $bio = null;

    /**
     * @OA\Property(type="string", format="date", example="1980-05-15", nullable=true)
     */
    public ?string $birth_date = null;

    /**
     * @OA\Property(type="string", format="date", example=null, nullable=true)
     */
    public ?string $death_date = null;

    /**
     * @OA\Property(type="string", example="American", nullable=true)
     */
    public ?string $nationality = null;

    /**
     * @OA\Property(type="string", format="url", example="https://johndoe.com", nullable=true)
     */
    public ?string $website = null;

    /**
     * @OA\Property(type="boolean", example=true)
     */
    public bool $is_active = true;

    /**
     * @OA\Property(type="integer", example=5, nullable=true)
     */
    public ?int $books_count = null;

    /**
     * @OA\Property(type="string", format="url", example="https://example.com/avatar.jpg", nullable=true)
     */
    public ?string $avatar = null;

    /**
     * @OA\Property(type="boolean", example=false)
     */
    public bool $can_delete = false;

    /**
     * @OA\Property(type="integer", example=43, nullable=true)
     */
    public ?int $age = null;

    /**
     * @OA\Property(type="string", format="date-time", example="2024-03-09 14:25:36")
     */
    public string $created_at;

    /**
     * @OA\Property(type="string", format="date-time", example="2024-03-10 08:15:22")
     */
    public string $updated_at;

    /**
     * Create a DTO from an Author model
     */
    public static function fromAuthor(Author $author): self
    {
        $dto = self::fromSource($author);
        $dto->avatar = $author->avatar?->image_path;
        $dto->can_delete = $author->books->isEmpty();
        $dto->books_count = $author->books->count();
        return $dto;
    }
}
