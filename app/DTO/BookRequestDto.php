<?php

namespace App\DTO;

/**
 * @OA\Schema(
 *     schema="BookRequest",
 *     title="Book Request DTO",
 *     description="Request body untuk membuat atau mengupdate Book"
 * )
 */
class BookRequestDto extends BaseDto
{
    /**
     * @OA\Property(type="string", example="The Great Gatsby", nullable=false)
     */
    public ?string $title = null;

    /**
     * @OA\Property(type="string", example="978-3-16-148410-0", nullable=true)
     */
    public ?string $isbn = null;

    /**
     * @OA\Property(type="integer", example=1, nullable=true)
     */
    public ?int $category_id = null;

    /**
     * @OA\Property(type="integer", example=2, nullable=true)
     */
    public ?int $publisher_id = null;

    /**
     * @OA\Property(type="string", example="2023-01-15", format="date", nullable=true)
     */
    public ?string $publish_date = null;

    /**
     * @OA\Property(type="integer", example=320, nullable=true)
     */
    public ?int $pages = null;

    /**
     * @OA\Property(type="string", example="A classic novel about the American Dream", nullable=true)
     */
    public ?string $description = null;

    /**
     * @OA\Property(type="string", example="published", nullable=true)
     */
    public ?string $status = null;

    /**
     * @OA\Property(type="number", format="float", example=29.99, nullable=true)
     */
    public ?float $price = null;

    /**
     * @OA\Property(type="integer", example=1, nullable=true)
     */
    public ?int $is_featured = null;

    /**
     * @OA\Property(type="string", example="en", nullable=true)
     */
    public ?string $language = null;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="integer"),
     *     example={1, 2, 3},
     *     description="IDs of authors associated with the book",
     *     nullable=true
     * )
     */
    public ?array $author_ids = null;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="string",
     *         format="binary"
     *     ),
     *     description="Book images (cover and previews)",
     *     nullable=true
     * )
     */
    public ?array $images = null;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="string",
     *         format="binary"
     *     ),
     *     description="Book files (PDF, EPUB, etc.)",
     *     nullable=true
     * )
     */
    public ?array $files = null;

    /**
     * Creates validation rules based on the DTO properties
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'publisher_id' => ['nullable', 'integer', 'exists:publishers,id'],
            'publish_date' => ['nullable', 'date'],
            'pages' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'max:50'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:pdf,doc,docx,epub,mobi', 'max:10240'],
        ];
    }
}
