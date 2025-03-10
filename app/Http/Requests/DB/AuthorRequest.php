<?php

namespace App\Http\Requests\DB;

use App\DTO\AuthorRequestDto;
use App\Http\Requests\Traits\AuthorRules;
use Illuminate\Foundation\Http\FormRequest;

class AuthorRequest extends FormRequest
{
    use AuthorRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge($this->baseRules(), $this->webRules(), [
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    /**
     * Convert the request to a DTO.
     *
     * @return AuthorRequestDto
     */
    public function toDto(): AuthorRequestDto
    {
        return AuthorRequestDto::fromSource($this->validated());
    }
}
