<?php

namespace App\Services\DB\Contracts;

use App\Models\Book;
use Illuminate\Http\UploadedFile;
use App\Services\DB\BaseServiceInterface;

interface BookServiceInterface extends BaseServiceInterface
{
    public function getAllWithRelations(int $perPage = 10): array;

    public function syncAuthors(Book $book, array $authorIds): void;

    public function handleBookImages(Book $book, array $images = []): void;

    public function handleBookFiles(Book $book, array $files = []): void;
}
