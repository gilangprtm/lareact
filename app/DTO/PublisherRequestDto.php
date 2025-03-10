<?php

namespace App\DTO;

/**
 * @OA\Schema(
 *     schema="PublisherRequest",
 *     title="Publisher Request DTO",
 *     description="Request body untuk membuat atau mengupdate Publisher"
 * )
 */
class PublisherRequestDto extends BaseDto
{
    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $name = null;

    /**
     * @OA\Property(type="string", example="Example text", format="email", nullable=true)
     */
    public ?string $email = null;

    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $phone = null;

    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $address = null;

    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $city = null;

    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $state = null;

    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $country = null;

    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $postal_code = null;

    /**
     * @OA\Property(type="string", example="Example text", nullable=true)
     */
    public ?string $website = null;

    /**
     * @OA\Property(
     *     property="logo",
     *     type="string",
     *     format="binary",
     *     description="Image file (JPEG, PNG, or GIF)",
     *     nullable=true
     * )
     */
    public $logo = null;

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
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'status' => ['nullable', 'string', 'max:255'],
        ];
    }
}
