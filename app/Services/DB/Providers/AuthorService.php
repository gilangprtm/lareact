<?php

namespace App\Services\DB\Providers;

use App\Models\Author;
use App\Services\DB\Contracts\AuthorServiceInterface;
use App\Services\DB\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class AuthorService extends BaseService implements AuthorServiceInterface
{
    /**
     * Path untuk menyimpan foto author
     */
    protected const PHOTO_PATH = 'authors';

    protected function getModel(): Model
    {
        return new Author();
    }

    protected function getFilterableFields(): array
    {
        return ['search'];
    }

    /**
     * Handle proses sebelum author dibuat
     * 
     * @param array &$data Data yang akan digunakan untuk membuat author
     */
    protected function beforeCreate(array &$data): void
    {
        $this->handlePhotoUpload($data);
    }

    /**
     * Handle proses sebelum author diupdate
     * 
     * @param array &$data Data yang akan digunakan untuk mengupdate author
     * @param mixed $id ID author yang akan diupdate
     */
    protected function beforeUpdate(array &$data, $id): void
    {
        $author = $this->find($id);
        $this->handlePhotoUpload($data, $author);
    }

    /**
     * Handle proses sebelum author dihapus
     * 
     * @param mixed $id ID author yang akan dihapus
     */
    protected function beforeDelete($id): void
    {
        $author = $this->find($id);
        if ($author->photo_path) {
            $this->deleteFile($author->photo_path);
        }
    }

    /**
     * Menangani upload foto author
     * 
     * @param array &$data Data request
     * @param Author|null $author Author yang ada jika update
     */
    protected function handlePhotoUpload(array &$data, ?Author $author = null): void
    {
        // Jika tidak ada file photo, tidak perlu diproses
        if (!isset($data['photo']) || !$data['photo'] instanceof UploadedFile) {
            return;
        }

        // Jika ada author (update) dan sudah punya photo_path, hapus foto lama
        if ($author && $author->photo_path) {
            $this->deleteFile($author->photo_path);
        }

        // Upload foto dan simpan path ke data
        $data['photo_path'] = $this->handleImageUpload($data['photo'], static::PHOTO_PATH);

        // Hapus field photo karena tidak ada di database
        unset($data['photo']);
    }

    public function getAllWithBooks(int $perPage = 10): LengthAwarePaginator
    {
        return $this->getPaginated(
            relations: ['books:id,title'],
            counts: ['books']
        );
    }
}
