# Pengelolaan Dokumentasi API

Proyek ini menggunakan L5-Swagger untuk membuat dokumentasi OpenAPI/Swagger dan dilengkapi dengan command kustom untuk mengelola dokumentasi API secara dinamis.

## Command API Docs

```bash
php artisan api:docs [--regenerate]
```

### Parameter

- `--regenerate` : Menghasilkan ulang dokumentasi dari anotasi OpenAPI (opsional)

### Fungsi

Command ini memungkinkan pengelolaan dokumentasi API yang dinamis dengan:

1. Menghasilkan dokumentasi OpenAPI dari anotasi di controller (dengan flag `--regenerate`)
2. Mengupdate path API di dokumentasi berdasarkan named routes yang terdaftar
3. Mempertahankan konsistensi antara anotasi OpenAPI dan struktur rute sebenarnya

## Alur Kerja

### 1. Anotasi Controller

Anotasi OpenAPI didefinisikan dalam controller, contoh:

```php
/**
 * @OA\Get(
 *     path="/api/v1/categories",
 *     summary="Retrieve all categories",
 *     ...
 * )
 */
public function index(Request $request) { ... }
```

### 2. Named Routes di API

Setiap rute API harus memiliki nama dengan format `api.*`:

```php
Route::prefix('v1')->group(function () {
    Route::get('categories', [CategoryController::class, 'index'])->name('api.categories.index');
    // ...
});
```

### 3. Regenerasi Dokumentasi

Setelah mengubah struktur rute atau menambahkan endpoint baru:

```bash
php artisan api:docs --regenerate
```

## Bagaimana Cara Kerjanya

1. Command `l5-swagger:generate` dijalankan untuk menghasilkan dokumentasi dari anotasi
2. File JSON yang dihasilkan dibaca dan diproses
3. Path di file JSON dicocokkan dengan named routes yang terdaftar di aplikasi
4. File JSON diperbarui dengan path yang benar dari named routes
5. Dokumentasi API yang diperbarui disimpan kembali

## Kasus Penggunaan

### Mengubah Prefix API

Jika Anda perlu mengubah prefix API (misalnya dari `/api` menjadi `/api/v2`):

1. Perbarui konfigurasi rute di `routes/api.php`:

    ```php
    Route::prefix('v2')->group(function () {
        // Rute API...
    });
    ```

2. Jalankan command:
    ```bash
    php artisan api:docs --regenerate
    ```

Command akan secara otomatis memperbarui semua path di dokumentasi API untuk mencerminkan prefix baru.

### Menambahkan Endpoint Baru

1. Tambahkan anotasi OpenAPI di controller
2. Tambahkan rute baru dengan named route di `routes/api.php`
3. Jalankan command:
    ```bash
    php artisan api:docs --regenerate
    ```

## Tips dan Praktik Terbaik

1. **Selalu gunakan format `api.*` untuk named routes API**

    ```php
    Route::get('categories', [CategoryController::class, 'index'])->name('api.categories.index');
    ```

2. **Jalankan `api:docs --regenerate` setelah perubahan rute atau controller**

    ```bash
    php artisan api:docs --regenerate
    ```

3. **Pastikan anotasi OpenAPI sesuai dengan struktur rute**

    ```php
    // Dalam controller:
    /**
     * @OA\Get(
     *     path="/api/v1/categories", // Ini harus sesuai dengan prefix route
     *     ...
     * )
     */
    ```

4. **Saat hanya ingin memperbarui path (tanpa regenerasi penuh), gunakan**:
    ```bash
    php artisan api:docs
    ```

## Troubleshooting

### Path di Dokumentasi Tidak Diperbarui

- Pastikan route memiliki nama dengan format `api.*`
- Coba jalankan dengan flag `--regenerate`
- Periksa format path di anotasi OpenAPI

### Dokumentasi Tidak Terbaru

- Jalankan `php artisan config:clear`
- Jalankan `php artisan cache:clear`
- Jalankan `php artisan api:docs --regenerate`
