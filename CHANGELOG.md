# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan dalam file ini.

## [1.1.0] - 2024-05-xx

### Tambahan

- Implementasi DTO (Data Transfer Objects) untuk konsistensi data dan dokumentasi API
- Penambahan generator kode untuk DTO dari model database:
    - Command `make:dto`: Generate DTO dari model
    - Command `make:api-classes`: Generate Request dan Resource dari DTO
    - Command `make:api-controller`: Generate Controller API dengan dokumentasi
- Pengembangan sistem dokumentasi API berbasis OpenAPI/Swagger
- Refaktor controller API untuk menggunakan pola DRY dengan DTO

### Perubahan

- Perubahan struktur folder dengan penambahan direktori DTO
- Pembaruan dokumentasi API dengan sistem otomatisasi berbasis DTO
- Standardisasi response API menggunakan ApiController base class

## [1.0.0] - 2024-xx-xx

### Tambahan

- Initial release
- CRUD dasar untuk entitas Book, Author, Publisher, dan Category
- Implementasi API REST
- Frontend dengan React dan Inertia.js
