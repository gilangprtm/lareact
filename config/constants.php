<?php

return [
    'pagination' => [
        'per_page' => [
            'default' => 15,
            'max' => 100,
        ],
    ],

    'files' => [
        'max_size' => [
            'image' => 2048, // 2MB in KB
            'document' => 5120, // 5MB in KB
        ],
        'allowed_types' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'document' => ['pdf', 'doc', 'docx', 'epub'],
        ],
    ],

    'categories' => [
        'max_depth' => 3,
    ],

    'cache' => [
        'ttl' => [
            'books' => 3600, // 1 hour
            'categories' => 7200, // 2 hours
            'publishers' => 7200,
            'authors' => 7200,
        ],
    ],

    'validation' => [
        'isbn' => [
            'formats' => ['ISBN-10', 'ISBN-13'],
            'regex' => '/^(?:ISBN(?:-1[03])?:? )?(?=[0-9X]{10}$|(?=(?:[0-9]+[- ]){3})[- 0-9X]{13}$|97[89][0-9]{10}$|(?=(?:[0-9]+[- ]){4})[- 0-9]{17}$)(?:97[89][- ]?)?[0-9]{1,5}[- ]?[0-9]+[- ]?[0-9]+[- ]?[0-9X]$/',
        ],
    ],
];
