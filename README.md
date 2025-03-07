# Bukubook

Aplikasi manajemen buku menggunakan Laravel + React (Inertia.js)

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
  └── ...

book_authors (pivot)
  ├── book_id (fk)
  └── author_id (fk)

book_images
  ├── id
  ├── book_id (fk)
  └── ...

book_files
  ├── id
  ├── book_id (fk)
  └── ...
```

### Architecture

```
app/
├── Http/
│   └── Controllers/
│       └── DB/                 # Controllers untuk business logic
│           ├── BaseController
│           ├── CategoryController
│           ├── PublisherController
│           ├── BookController
│           └── AuthorController
├── Models/                     # Eloquent models
├── Services/
│   └── DB/                    # Service layer
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
