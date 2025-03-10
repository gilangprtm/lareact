<?php

namespace App\DTO;

/**
 * @OA\Schema(
 *     schema="AuthorRequest",
 *     title="Author Request DTO",
 *     description="Request body untuk membuat atau mengupdate Author"
 * )
 */
class AuthorRequestDto extends BaseDto
{
    /**
     * @OA\Property(type="string", example="John Doe", nullable=false)
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
     * @OA\Property(
     *     property="photo",
     *     type="string",
     *     format="binary",
     *     description="Author photo (JPEG, PNG, or GIF)",
     *     nullable=true
     * )
     */
    public $photo = null;

    /**
     * Creates validation rules based on the DTO properties
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }
}
