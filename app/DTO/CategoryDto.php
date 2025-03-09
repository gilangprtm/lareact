<?php

namespace App\DTO;
use App\Models\Category;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Category",
 *     title="Category DTO",
 *     description="Data Transfer Object for Category"
 * )
 */
class CategoryDto extends BaseDto
{
    /**
     * @OA\Property(type="int", example=42)
     */
    public int $id;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $name = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $slug = null;

    /**
     * @OA\Property(type="string", example="Example", format="ipv4", nullable=true)
     */
    public ?string $description = null;

    /**
     * @OA\Property(type="int", example=42, nullable=true)
     */
    public ?int $parent_id = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $status = null;

    /**
     * @OA\Property(type="string", example="Example", format="date-time", nullable=true)
     */
    public ?string $created_at = null;

    /**
     * @OA\Property(type="string", example="Example", format="date-time", nullable=true)
     */
    public ?string $updated_at = null;

    /**
     * Create a DTO from a Category model
     *
     * @param Category $model The model instance
     * @return self
     */
    public static function fromModel(Category $model): self
    {
        $dto = self::fromSource($model);

        // Handle date fields
        $dto->created_at = $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : null;
        $dto->updated_at = $model->updated_at ? $model->updated_at->format('Y-m-d H:i:s') : null;

        // Ensure slug is set
        if (empty($dto->slug) && !empty($dto->name)) {
            $dto->slug = \Illuminate\Support\Str::slug($dto->name);
        }
        return $dto;
    }
}
