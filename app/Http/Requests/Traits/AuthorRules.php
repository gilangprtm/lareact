<?php

namespace App\Http\Requests\Traits;

trait AuthorRules
{
    protected function baseRules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'bio' => ['nullable', 'string'],
        ];
    }

    /**
     * Get web-specific rules in addition to the base rules.
     */
    protected function webRules()
    {
        return [
            // Web-specific validation rules for Author
            // Example: 'image' => ['nullable', 'image', 'max:2048']
        ];
    }

    /**
     * Get API-specific rules in addition to the base rules.
     */
    protected function apiRules()
    {
        return [
            // API-specific validation rules for Author
            // Example: 'image_url' => ['nullable', 'url']
        ];
    }
}
