<?php

namespace App\DTO;

/**
 * @OA\Schema(
 *     schema="CategoryRequest",
 *     title="Category Request DTO",
 *     description="Request body untuk membuat atau mengupdate Category"
 * )
 */
class CategoryRequestDto extends BaseDto
{
    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $name = null;

    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $slug = null;

    /**
     * @OA\Property(type="string", example="Example text", format="ipv4", nullable=true)
     */
    public ?string $description = null;

    /**
     * @OA\Property(type="int", example="42", nullable=true)
     */
    public ?int $parent_id = null;

    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $status = null;

    /**
     * Creates validation rules based on the DTO properties
     */
    public static function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:255'],
        ];
    }
}
