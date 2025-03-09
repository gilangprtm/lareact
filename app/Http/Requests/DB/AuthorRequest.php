<?php

namespace App\Http\Requests\DB;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="AuthorRequest",
 *     title="Author Request",
 *     description="Request body for creating or updating an author",
 *     type="object",
 *     required={"name"}
 * )
 */
class AuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @OA\Property(property="name", type="string", example="John Doe", description="Author's name", maxLength=255)
     * @OA\Property(property="email", type="string", format="email", nullable=true, example="johndoe@example.com", description="Author's email", maxLength=255)
     * @OA\Property(property="bio", type="string", nullable=true, example="A famous author.", description="Author's biography")
     * @OA\Property(property="photo", type="string", format="binary", nullable=true, description="Profile picture (image file, max 2MB)")
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'], // 2MB Max
        ];
    }
}
