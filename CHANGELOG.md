# Changelog

Dokumentasi perubahan dan progress pengembangan project Bukubook.

## [Unreleased]

## [0.1.0] - 2024-03-14

### Added

- Database Structure

    - Migration untuk categories table dengan fields: name, slug, description, parent_id, status
    - Migration untuk publishers table dengan fields: name, email, phone, address, city, state, country, postal_code, website, logo_path, status
    - Migration untuk books table dengan fields: title, isbn, category_id, publisher_id, publish_date, pages, description, status, price, is_featured, language
    - Migration untuk authors table
    - Migration untuk book_authors pivot table
    - Migration untuk book_images table
    - Migration untuk book_files table
    - Foreign key constraints dengan ON DELETE RESTRICT

- Models & Enums

    - Enum BookStatus: draft, published, out_of_stock
    - Enum PublisherStatus: active, inactive, suspended
    - Enum CategoryStatus: active, inactive
    - Model relationships dan fillable fields

- Service Layer

    - BaseService dengan method CRUD standar
    - BaseServiceInterface untuk contract
    - Service classes untuk Category, Book, Publisher, Author
    - Business validations (cek relasi sebelum delete)

- Controllers

    - Controllers di namespace App\Http\Controllers\DB
    - Menggunakan service layer untuk operasi database
    - Method standar: index, show, create, update, delete

- Routes

    - Web routes untuk aplikasi monolith dengan Inertia.js
    - API routes dengan versioning (v1)
    - Public dan protected routes
    - Menggunakan DB controllers untuk kedua routes

- Database Seeding
    - Factories untuk Category, Publisher, Book, Author
    - Seeder untuk generate data testing
    - Relasi antar data dalam seeder

### Changed

- Perubahan dari getAllPaginated ke getPaginated di BaseService
- Optimasi query di service layer dengan eager loading

### Fixed

- Foreign key constraints dari cascade ke restrict
- Status field di publishers table
