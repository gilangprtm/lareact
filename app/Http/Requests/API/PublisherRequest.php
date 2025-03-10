<?php

namespace App\Http\Requests\API;

use App\DTO\PublisherRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class PublisherRequest extends FormRequest
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
        return PublisherRequestDto::rules();
    }
    
    /**
     * Convert validated input to DTO
     */
    public function toDto(): PublisherRequestDto
    {
        return PublisherRequestDto::fromSource($this->validated());
    }
}
