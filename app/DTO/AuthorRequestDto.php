<?php

namespace App\DTO;

/**
 * @OA\Schema(
 *     schema="AuthorRequest",
 *     title="Author Request DTO",
 *     description="Request body untuk membuat atau mengupdate author"
 * )
 */
class AuthorRequestDto extends BaseDto
{
    /**
     * @OA\Property(type="string", example="John Doe", description="Author's name", maxLength=255)
     */
    public string $name;

    /**
     * @OA\Property(type="string", format="email", nullable=true, example="johndoe@example.com", description="Author's email", maxLength=255)
     */
    public ?string $email = null;

    /**
     * @OA\Property(type="string", nullable=true, example="A famous author.", description="Author's biography")
     */
    public ?string $bio = null;

    /**
     * @OA\Property(type="string", format="date", nullable=true, example="1980-05-15", description="Author's birth date")
     */
    public ?string $birth_date = null;

    /**
     * @OA\Property(type="string", format="uri", nullable=true, example="https://example.com", description="Author's website URL")
     */
    public ?string $website = null;

    /**
     * Creates validation rules based on the DTO properties
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'bio' => ['nullable', 'string'],
            'birth_date' => ['nullable', 'date'],
            'website' => ['nullable', 'url'],
        ];
    }
}
