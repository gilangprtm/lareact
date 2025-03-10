<?php

namespace App\DTO;

use App\Models\Book;

/**
 * @OA\Schema(
 *     schema="Book",
 *     title="Book DTO",
 *     description="Data Transfer Object for Book"
 * )
 */
class BookDto extends BaseDto
{
    /**
     * @OA\Property(type="integer", example=1)
     */
    public int $id;

    /**
     * @OA\Property(type="string", example="The Great Gatsby", nullable=true)
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
     * @OA\Property(type="boolean", example=true, nullable=true)
     */
    public ?bool $is_featured = null;

    /**
     * @OA\Property(type="string", example="en", nullable=true)
     */
    public ?string $language = null;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="F. Scott Fitzgerald")
     *     ),
     *     description="Authors of the book",
     *     nullable=true
     * )
     */
    public ?array $authors = null;

    /**
     * @OA\Property(
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="Fiction"),
     *     nullable=true
     * )
     */
    public ?object $category = null;

    /**
     * @OA\Property(
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=2),
     *     @OA\Property(property="name", type="string", example="Penguin Books"),
     *     nullable=true
     * )
     */
    public ?object $publisher = null;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="image_path", type="string", example="books/images/cover.jpg"),
     *         @OA\Property(property="image_url", type="string", example="http://example.com/storage/books/images/cover.jpg"),
     *         @OA\Property(property="image_type", type="string", example="cover"),
     *         @OA\Property(property="sort_order", type="integer", example=0)
     *     ),
     *     description="Images of the book",
     *     nullable=true
     * )
     */
    public ?array $images = null;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="file_path", type="string", example="books/files/sample.pdf"),
     *         @OA\Property(property="file_url", type="string", example="http://example.com/storage/books/files/sample.pdf"),
     *         @OA\Property(property="file_type", type="string", example="pdf"),
     *         @OA\Property(property="file_name", type="string", example="Sample Chapter.pdf")
     *     ),
     *     description="Files associated with the book",
     *     nullable=true
     * )
     */
    public ?array $files = null;

    /**
     * @OA\Property(type="string", example="2023-01-15 14:30:45", format="date-time", nullable=true)
     */
    public ?string $created_at = null;

    /**
     * @OA\Property(type="string", example="2023-02-20 09:15:30", format="date-time", nullable=true)
     */
    public ?string $updated_at = null;

    /**
     * Create a DTO from a Book model
     *
     * @param Book $model The model instance
     * @return self
     */
    public static function fromModel(Book $model): self
    {
        $dto = self::fromSource($model);

        // Handle date fields
        $dto->publish_date = $model->publish_date ? $model->publish_date->format('Y-m-d') : null;
        $dto->created_at = $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : null;
        $dto->updated_at = $model->updated_at ? $model->updated_at->format('Y-m-d H:i:s') : null;

        // Handle relationships
        if ($model->relationLoaded('category')) {
            $dto->category = (object) [
                'id' => $model->category->id,
                'name' => $model->category->name
            ];
        }

        if ($model->relationLoaded('publisher')) {
            $dto->publisher = (object) [
                'id' => $model->publisher->id,
                'name' => $model->publisher->name
            ];
        }

        if ($model->relationLoaded('authors')) {
            $dto->authors = $model->authors->map(function ($author) {
                return [
                    'id' => $author->id,
                    'name' => $author->name
                ];
            })->toArray();
        }

        // Handle images with URLs
        if ($model->relationLoaded('images')) {
            $dto->images = $model->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_path' => $image->image_path,
                    'image_url' => asset('storage/' . $image->image_path),
                    'image_type' => $image->image_type,
                    'sort_order' => $image->sort_order
                ];
            })->toArray();
        }

        // Handle files with URLs
        if ($model->relationLoaded('files')) {
            $dto->files = $model->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_path' => $file->file_path,
                    'file_url' => asset('storage/' . $file->file_path),
                    'file_type' => $file->file_type,
                    'file_name' => $file->file_name
                ];
            })->toArray();
        }

        return $dto;
    }
}
