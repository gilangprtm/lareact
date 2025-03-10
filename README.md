# Bukubook

Aplikasi manajemen buku menggunakan Laravel + React (Inertia.js)

## Dokumentasi Generator API

Proyek ini menggunakan pendekatan Data Transfer Object (DTO) untuk menghasilkan API yang konsisten dan terdokumentasi dengan baik. Lihat dokumentasi berikut untuk informasi lebih lanjut:

- [Alur Kerja Generator API](docs/generator-workflow.md) - Penjelasan tentang alur kerja lengkap generator API
- [Diagram Generator API](docs/generator-diagram.md) - Diagram visual alur penggunaan generator
- [FAQ DTO dan Generator](docs/dto-faq.md) - Pertanyaan umum tentang pendekatan DTO
- [Pengelolaan Dokumentasi API](docs/api-docs-management.md) - Panduan untuk mengelola dokumentasi API
- [Changelog](CHANGELOG.md) - Log perubahan dan pengembangan proyek

## Struktur Project

### Database

```
categories
  ├── id
  ├── name
  ├── slug
  ├── description (nullable)
  ├── parent_id (nullable, self-referencing)
  └── status (active/inactive)

publishers
  ├── id
  ├── name
  ├── email
  ├── phone
  ├── address
  ├── city
  ├── state
  ├── country
  ├── postal_code
  ├── website (nullable)
  ├── logo_path (nullable)
  └── status (active/inactive/suspended)

books
  ├── id
  ├── title
  ├── isbn (unique)
  ├── category_id (fk)
  ├── publisher_id (fk)
  ├── publish_date (nullable)
  ├── pages (nullable)
  ├── description (nullable)
  ├── status (draft/published/out_of_stock)
  ├── price
  ├── is_featured
  └── language

authors
  ├── id
  ├── name
  ├── email
  ├── bio
  └── photo_path

book_authors (pivot)
  ├── book_id (fk)
  └── author_id (fk)

book_images
  ├── id
  ├── book_id (fk)
  ├── image_path
  ├── image_type
  └── sort_order

book_files
  ├── id
  ├── book_id (fk)
  ├── file_path
  ├── file_type
  ├── file_name
  └── description (nullable)
```

### Architecture

```
app/
├── Console/                    # Artisan commands
│   └── Commands/
│       ├── GenerateDtoFromModel.php
│       ├── GenerateApiClassesFromDto.php
│       └── GenerateApiController.php
├── DTO/                        # Data Transfer Objects
│   ├── BaseDto.php
│   ├── AuthorDto.php
│   ├── AuthorRequestDto.php
│   └── ...
├── Http/
│   ├── Controllers/
│   │   ├── ApiController.php   # Base API controller
│   │   ├── DB/                 # Controllers untuk business logic
│   │   │   ├── BaseController
│   │   │   ├── CategoryController
│   │   │   ├── PublisherController
│   │   │   ├── BookController
│   │   │   └── AuthorController
│   │   └── API/                # API Controllers
│   │       ├── AuthorController
│   │       ├── BookController
│   │       ├── CategoryController
│   │       └── PublisherController
│   ├── Requests/
│   │   └── API/                # Form requests
│   │       ├── AuthorRequest
│   │       └── ...
│   └── Resources/
│       └── API/                # API Resources
│           ├── AuthorResource
│           └── ...
├── Models/                     # Eloquent models
├── Services/
│   └── DB/                     # Service layer
│       ├── BaseService
│       ├── CategoryService
│       ├── PublisherService
│       ├── BookService
│       └── AuthorService
└── Enums/                     # PHP 8.1 Enums
    ├── BookStatus
    ├── PublisherStatus
    └── CategoryStatus

routes/
├── web.php                    # Routes untuk web (Inertia)
└── api.php                    # Routes untuk API

database/
├── migrations/                # Database migrations
├── factories/                 # Model factories
└── seeders/                  # Database seeders
```

## Setup

1. Clone repository
2. Copy `.env.example` ke `.env`
3. Set database credentials di `.env`
4. Install dependencies:

```bash
composer install
npm install
```

5. Generate app key:

```bash
php artisan key:generate
```

6. Run migrations dan seeders:

```bash
php artisan migrate:fresh --seed
```

7. Run development server:

```bash
php artisan serve
npm run dev
```

## API Documentation

API diimplementasikan dengan menggunakan pola DRY (Don't Repeat Yourself) menggunakan Data Transfer Objects (DTO). Dokumentasi OpenAPI/Swagger otomatis dihasilkan dari skema DTO ini.

### Mengakses Dokumentasi

Dokumentasi API dapat diakses melalui endpoint:

```
/api/docs
```

### Mengelola Dokumentasi API

Proyek ini dilengkapi dengan command khusus untuk mengelola dokumentasi API secara dinamis:

```bash
# Regenerasi dokumentasi API dan perbarui paths
php artisan api:docs --regenerate

# Memperbarui path di dokumentasi tanpa regenerasi penuh
php artisan api:docs
```

Command ini akan:

1. Menghasilkan dokumentasi Swagger dari anotasi OpenAPI (jika menggunakan flag `--regenerate`)
2. Menggunakan named routes untuk memperbarui path di dokumentasi API secara dinamis
3. Memastikan dokumentasi tetap konsisten meskipun prefix rute berubah

> **Penting:** Setelah mengubah struktur rute di `routes/api.php`, selalu jalankan `php artisan api:docs --regenerate` untuk memastikan dokumentasi tetap akurat.

### Generator Kode API

Aplikasi ini dilengkapi dengan generator kode Artisan untuk mempercepat pengembangan API dengan prinsip DRY:

#### 1. Menghasilkan DTO dari Model

```bash
php artisan make:dto Author
```

Perintah ini akan menghasilkan:

- `app/DTO/AuthorDto.php`: Representasi data dengan anotasi OpenAPI
- `app/DTO/AuthorRequestDto.php`: Definisi request body dengan validasi

Options:

- `--path=app/DTO`: Path untuk menyimpan file DTO (default: app/DTO)
- `--force`: Timpa file yang sudah ada

#### 2. Menghasilkan Form Request dan Resource

```bash
php artisan make:api-classes AuthorDto
```

Perintah ini akan menghasilkan:

- `app/Http/Requests/API/AuthorRequest.php`: Form request yang menggunakan validasi dari DTO
- `app/Http/Resources/API/AuthorResource.php`: Resource yang menggunakan DTO untuk transformasi

Options:

- `--path=app/Http`: Path dasar untuk kelas API (default: app/Http)
- `--force`: Timpa file yang sudah ada

#### 3. Menghasilkan Controller API

```bash
php artisan make:api-controller Author
```

Perintah ini akan menghasilkan:

- `app/Http/Controllers/API/AuthorController.php`: Controller API lengkap dengan endpoint CRUD dan dokumentasi OpenAPI

Options:

- `--module=v1`: Nama modul API (contoh: v1 untuk /api/v1/)
- `--path=app/Http/Controllers/API`: Path untuk menyimpan controller
- `--force`: Timpa file yang sudah ada

### Public Endpoints

```
GET /api/v1/categories
GET /api/v1/categories/{id}
GET /api/v1/publishers
GET /api/v1/publishers/{id}
GET /api/v1/books
GET /api/v1/books/{id}
GET /api/v1/authors
GET /api/v1/authors/{id}
```

### Protected Endpoints

```
# Categories
POST   /api/v1/categories
PUT    /api/v1/categories/{id}
DELETE /api/v1/categories/{id}

# Publishers
POST   /api/v1/publishers
PUT    /api/v1/publishers/{id}
DELETE /api/v1/publishers/{id}

# Books
POST   /api/v1/books
PUT    /api/v1/books/{id}
DELETE /api/v1/books/{id}

# Authors
POST   /api/v1/authors
PUT    /api/v1/authors/{id}
DELETE /api/v1/authors/{id}
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
