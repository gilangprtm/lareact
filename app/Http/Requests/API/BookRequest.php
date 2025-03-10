<?php

namespace App\Http\Requests\API;

use App\DTO\BookRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
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
        // Menggunakan rules dari DTO
        return BookRequestDto::rules();
    }
    
    /**
     * Convert validated input to DTO
     */
    public function toDto(): BookRequestDto
    {
        return BookRequestDto::fromSource($this->validated());
    }
}
