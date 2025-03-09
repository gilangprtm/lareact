# Alur Kerja Generator Kode API

Dokumen ini menjelaskan alur kerja dan cara menggunakan generator kode API dalam proyek ini.

## Konsep Dasar

Generator kode API dirancang dengan prinsip DRY (Don't Repeat Yourself) untuk mengotomatisasi pembuatan kode boilerplate API sambil mempertahankan konsistensi antara:

1. Struktur database
2. Validasi request
3. Transformasi response
4. Dokumentasi API

## Arsitektur

```
Model Database → DTO → Request/Resource → Controller API
```

1. **Data Transfer Object (DTO)**: Representasi data dengan anotasi OpenAPI
2. **Form Request**: Validasi input berdasarkan skema DTO
3. **Resource**: Transformasi output menggunakan DTO
4. **Controller API**: Endpoint CRUD dengan dokumentasi dari DTO

## Alur Kerja Lengkap

### 1. Membuat Model dan Migrasi

Pertama, buat model dan migrasi database seperti biasa:

```bash
php artisan make:model Category -m
```

Setelah mendefinisikan kolom di migrasi dan relasi di model, jalankan migrasi:

```bash
php artisan migrate
```

### 2. Membuat DTO dari Model

Buat DTO dan RequestDTO otomatis dari model:

```bash
php artisan make:dto Category
```

Ini akan menghasilkan:

- `app/DTO/CategoryDto.php`
- `app/DTO/CategoryRequestDto.php`

### 3. Membuat Form Request dan Resource

Buat Form Request dan Resource otomatis dari DTO:

```bash
php artisan make:api-classes CategoryDto
```

Ini akan menghasilkan:

- `app/Http/Requests/API/CategoryRequest.php`
- `app/Http/Resources/API/CategoryResource.php`

### 4. Membuat Controller API

Buat Controller API lengkap dengan dokumentasi OpenAPI:

```bash
php artisan make:api-controller Category
```

Ini akan menghasilkan:

- `app/Http/Controllers/API/CategoryController.php`

### 5. Mendaftarkan Route API

Tambahkan route resource di `routes/api.php`:

```php
Route::apiResource('categories', \App\Http\Controllers\API\CategoryController::class);
```

Untuk versi API:

```php
Route::prefix('v1')->group(function () {
    Route::apiResource('categories', \App\Http\Controllers\API\V1\CategoryController::class);
});
```

## Contoh Penggunaan untuk Entitas Baru

Misalkan kita ingin membuat API untuk entitas "Tag":

1. **Buat model dan migrasi**:

    ```bash
    php artisan make:model Tag -m
    ```

2. **Definisikan struktur migrasi**:

    ```php
    Schema::create('tags', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->timestamps();
    });
    ```

3. **Definisikan relasi di model**:

    ```php
    class Tag extends Model
    {
        protected $fillable = ['name', 'slug', 'description'];

        public function books()
        {
            return $this->belongsToMany(Book::class);
        }
    }
    ```

4. **Jalankan migrasi**:

    ```bash
    php artisan migrate
    ```

5. **Generate DTO**:

    ```bash
    php artisan make:dto Tag
    ```

6. **Generate API Classes**:

    ```bash
    php artisan make:api-classes TagDto
    ```

7. **Generate Controller**:

    ```bash
    php artisan make:api-controller Tag
    ```

8. **Tambahkan Routes**:

    ```php
    Route::apiResource('tags', \App\Http\Controllers\API\TagController::class);
    ```

9. **Akses API dan Dokumentasi**:
    - API Endpoint: `/api/tags`
    - Dokumentasi: `/api/docs`

## Kustomisasi

Anda dapat menyesuaikan file yang dihasilkan:

1. **Kustomisasi DTO**: Edit file DTO untuk menambahkan properti atau metode
2. **Kustomisasi Validasi**: Edit rules() di RequestDTO
3. **Kustomisasi Transformasi**: Edit fromModel() di DTO
4. **Kustomisasi Controller**: Tambahkan endpoint khusus atau logika bisnis

## Troubleshooting

### DTO Tidak Mendeteksi Semua Kolom

- Pastikan model memiliki table property atau getTable() method
- Pastikan database telah dimigrasi dengan benar

### Validasi Tidak Bekerja

- Pastikan RequestDTO memiliki rules() method yang sesuai
- Pastikan FormRequest menggunakan rules dari DTO

### Dokumentasi API Tidak Muncul

- Jalankan `php artisan cache:clear`
- Jalankan `php artisan config:clear`
- Periksa konfigurasi OpenAPI/Swagger
