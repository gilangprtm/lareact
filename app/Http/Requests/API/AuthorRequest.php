<?php

namespace App\Http\Requests\API;

use App\DTO\AuthorRequestDto;
use App\Http\Requests\Traits\AuthorRules;
use Illuminate\Foundation\Http\FormRequest;

class AuthorRequest extends FormRequest
{
    use AuthorRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->baseRules(), $this->apiRules(), [
            'photo_url' => ['nullable', 'url'],
        ]);
    }

    /**
     * Convert validated input to DTO
     */
    public function toDto(): AuthorRequestDto
    {
        return AuthorRequestDto::fromSource($this->validated());
    }
}
