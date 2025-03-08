<?php

namespace App\Services\DB\Providers;

use App\Models\Book;
use App\Services\DB\Contracts\BookServiceInterface;
use App\Services\DB\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class BookService extends BaseService implements BookServiceInterface
{
    protected function getModel(): Model
    {
        return new Book();
    }

    protected function getFilterableFields(): array
    {
        return ['search'];
    }

    protected function beforeCreate(array &$data): void
    {
        $this->extractAndUnsetData($data);
    }

    protected function afterCreate(Model $model, array $data): void
    {
        $this->handleRelatedData($model, $data);
        $model->load(['category:id,name', 'publisher:id,name', 'authors:id,name']);
    }

    protected function beforeUpdate(array &$data, $id): void
    {
        $this->extractAndUnsetData($data);
    }

    protected function afterUpdate(Model $model, array $data): void
    {
        $this->handleRelatedData($model, $data);
        $model->load(['category:id,name', 'publisher:id,name', 'authors:id,name']);
    }

    protected function beforeDelete($id): void
    {
        $book = $this->find($id);
        $this->handleBookImages($book, []);
        $this->handleBookFiles($book, []);
    }

    private function extractAndUnsetData(array &$data): array
    {
        $extracted = [
            'author_ids' => $data['author_ids'] ?? [],
            'images' => $data['images'] ?? [],
            'files' => $data['files'] ?? []
        ];

        unset($data['author_ids'], $data['images'], $data['files']);

        return $extracted;
    }

    private function handleRelatedData(Book $book, array $data): void
    {
        $extracted = $this->extractAndUnsetData($data);

        if ($extracted['author_ids']) {
            $this->syncAuthors($book, $extracted['author_ids']);
        }

        if ($extracted['images']) {
            $this->handleBookImages($book, $extracted['images']);
        }

        if ($extracted['files']) {
            $this->handleBookFiles($book, $extracted['files']);
        }
    }

    public function getAllWithRelations(int $perPage = 10): LengthAwarePaginator
    {
        return $this->getPaginated(
            relations: [
                'category:id,name,slug',
                'publisher:id,name',
                'authors:id,name',
                'images:id,book_id,image_path,image_type,sort_order',
                'files:id,book_id,file_path,file_name,file_type'
            ]
        );
    }

    public function syncAuthors(Book $book, array $authorIds): void
    {
        $this->sync($book, 'authors', $authorIds);
    }

    public function handleBookImages(Book $book, array $images = []): void
    {
        if (empty($images)) {
            return;
        }

        // Delete old images in a single query
        $oldImages = $book->images()->pluck('image_path')->toArray();
        foreach ($oldImages as $path) {
            $this->deleteFile($path);
        }
        $book->images()->delete();

        // Store new images
        $imagesToCreate = [];
        foreach ($images as $index => $image) {
            $imagePath = $this->handleImageUpload($image, 'books/images');
            $imagesToCreate[] = [
                'image_path' => $imagePath,
                'image_type' => $index === 0 ? 'cover' : 'preview',
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        $book->images()->insert($imagesToCreate);
    }

    public function handleBookFiles(Book $book, array $files = []): void
    {
        if (empty($files)) {
            return;
        }

        // Delete old files in a single query
        $oldFiles = $book->files()->pluck('file_path')->toArray();
        foreach ($oldFiles as $path) {
            $this->deleteFile($path);
        }
        $book->files()->delete();

        // Store new files
        $filesToCreate = [];
        foreach ($files as $file) {
            $filePath = $this->handleFileUpload($file, 'books/files');
            $fileInfo = $this->getFileInfo($file);
            $filesToCreate[] = array_merge(
                [
                    'file_path' => $filePath,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                $fileInfo
            );
        }
        $book->files()->insert($filesToCreate);
    }

    public function create(array $data)
    {
        try {
            $this->beginTransaction();

            $authorIds = $data['author_ids'] ?? [];
            $images = $data['images'] ?? [];
            $files = $data['files'] ?? [];

            // Remove non-fillable data
            unset($data['author_ids'], $data['images'], $data['files']);

            /** @var Book $book */
            $book = parent::create($data);

            if ($authorIds) {
                $this->syncAuthors($book, $authorIds);
            }

            if ($images) {
                $this->handleBookImages($book, $images);
            }

            if ($files) {
                $this->handleBookFiles($book, $files);
            }

            $this->commit();
            return $book->load(['category:id,name', 'publisher:id,name', 'authors:id,name']);
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function update(array $data, $id)
    {
        try {
            $this->beginTransaction();

            $authorIds = $data['author_ids'] ?? [];
            $images = $data['images'] ?? [];
            $files = $data['files'] ?? [];

            // Remove non-fillable data
            unset($data['author_ids'], $data['images'], $data['files']);

            /** @var Book $book */
            $book = parent::update($data, $id);

            if ($authorIds) {
                $this->syncAuthors($book, $authorIds);
            }

            if ($images) {
                $this->handleBookImages($book, $images);
            }

            if ($files) {
                $this->handleBookFiles($book, $files);
            }

            $this->commit();
            return $book->fresh(['category:id,name', 'publisher:id,name', 'authors:id,name']);
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $this->beginTransaction();

            /** @var Book $book */
            $book = $this->find($id);

            // Delete images and files
            $this->handleBookImages($book, []);
            $this->handleBookFiles($book, []);

            $result = parent::delete($id);

            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
}
