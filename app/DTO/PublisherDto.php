<?php

namespace App\DTO;
use App\Models\Publisher;

/**
 * @OA\Schema(
 *     schema="Publisher",
 *     title="Publisher DTO",
 *     description="Data Transfer Object for Publisher"
 * )
 */
class PublisherDto extends BaseDto
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
     * @OA\Property(type="string", example="Example", format="email", nullable=true)
     */
    public ?string $email = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $phone = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $address = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $city = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $state = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $country = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $postal_code = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $website = null;

    /**
     * @OA\Property(type="string", example="Example", nullable=true)
     */
    public ?string $logo_path = null;

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
     * Create a DTO from a Publisher model
     *
     * @param Publisher $model The model instance
     * @return self
     */
    public static function fromModel(Publisher $model): self
    {
        $dto = self::fromSource($model);

        // Handle date fields
        $dto->created_at = $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : null;
        $dto->updated_at = $model->updated_at ? $model->updated_at->format('Y-m-d H:i:s') : null;
        return $dto;
    }
}
