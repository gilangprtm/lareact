<?php

namespace App\Http\Requests\DB;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:13', Rule::unique('books')->ignore($this->book)],
            'category_id' => ['required', 'exists:categories,id'],
            'publisher_id' => ['required', 'exists:publishers,id'],
            'publish_date' => ['required', 'date'],
            'pages' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string'],
            'status' => ['required', Rule::in(['available', 'out_of_stock', 'coming_soon'])],
            'price' => ['required', 'numeric', 'min:0'],
            'is_featured' => ['boolean'],
            'language' => ['required', 'string', 'max:50'],
            'author_ids' => ['required', 'array', 'min:1'],
            'author_ids.*' => ['exists:authors,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'], // 2MB Max
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:10240'], // 10MB Max
        ];
    }
}
