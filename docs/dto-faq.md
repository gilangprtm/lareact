# FAQ: Data Transfer Objects (DTO) dan Generator Kode

## Pertanyaan Umum

### Apa itu DTO?

DTO (Data Transfer Object) adalah objek yang digunakan untuk mengangkut data antara subsistem aplikasi. Dalam konteks API, DTO membantu memisahkan representasi data eksternal dari model internal, menyediakan lapisan abstraksi yang memungkinkan perubahan pada model tanpa memengaruhi respons API.

### Mengapa menggunakan pendekatan DTO?

1. **Konsistensi**: Memastikan respons API konsisten terlepas dari perubahan model internal
2. **Isolasi**: Memisahkan logika bisnis dari representasi data
3. **Dokumentasi**: Menyediakan tempat tunggal untuk mendefinisikan skema API
4. **Validasi**: Menstandarisasi aturan validasi untuk input API
5. **DRY**: Menghindari duplikasi definisi skema di berbagai tempat (controller, resource, docs)

### Apa perbedaan DTO dan Resource Laravel?

- **Resource Laravel**: Fokus pada transformasi data untuk respons API, tanpa informasi skema atau validasi
- **DTO**: Menyediakan definisi skema lengkap yang dapat digunakan untuk validasi, transformasi, dan dokumentasi

## Generator Kode

### Bagaimana generator DTO bekerja?

Generator menganalisis model Laravel dan skema database untuk secara otomatis membuat:

1. DTO dengan properti yang sesuai dan anotasi OpenAPI
2. RequestDTO dengan aturan validasi berdasarkan skema database
3. FormRequest dan Resource yang menggunakan DTO

### Bisakah saya memodifikasi DTO yang dihasilkan?

Ya, file DTO yang dihasilkan adalah file PHP biasa yang dapat diedit. Generator menghasilkan kode awal yang dapat Anda sesuaikan lebih lanjut sesuai kebutuhan khusus Anda.

### Bagaimana menangani kasus khusus?

Generator memiliki beberapa metode yang dapat dimodifikasi:

- `fromModel()`: Menangani transformasi khusus dari model ke DTO
- `rules()`: Menentukan aturan validasi kustom

## OpenAPI / Swagger

### Bagaimana dokumentasi API dihasilkan?

Anotasi OpenAPI dalam DTO digunakan untuk menghasilkan dokumentasi API. Ini mencakup:

- Definisi skema
- Parameter endpoint
- Contoh request/response
- Anotasi keamanan

### Bagaimana memperbarui dokumentasi setelah perubahan?

Setelah mengubah DTO atau controller, jalankan:

```bash
php artisan cache:clear
php artisan config:clear
php artisan scramble:analyze
```

### Bisakah saya menambahkan informasi tambahan ke dokumentasi?

Ya, Anda dapat menambahkan anotasi OpenAPI tambahan di DTO, controller, atau file konfigurasi dokumentasi.

## Praktik Terbaik

### Kapan sebaiknya membuat ulang DTO?

- Saat ada perubahan signifikan pada struktur model
- Saat menambahkan kolom baru ke database
- Saat menambahkan relasi baru

### Bagaimana menangani relasi dalam DTO?

Generator secara otomatis mendeteksi relasi model dan menambahkannya ke DTO. Anda dapat menyesuaikan cara relasi ditransformasikan di metode `fromModel()`.

### Bagaimana menangani upload file?

Saat menggunakan DTO dengan upload file:

1. Definisikan properti file di DTO dengan anotasi yang sesuai
2. Gunakan `FormRequestBody` dengan `multipart/form-data`
3. Tangani upload file di controller seperti biasa

### Praktik terbaik untuk penamaan file dan struktur direktori?

1. DTO dan RequestDTO di direktori `app/DTO`
2. Request di direktori `app/Http/Requests/API`
3. Resource di direktori `app/Http/Resources/API`
4. Controller API di direktori `app/Http/Controllers/API`

## Pemecahan Masalah

### DTO Tidak Menghasilkan Semua Properti

- Pastikan kolom ada di database
- Periksa tipe data kolom untuk pemetaan yang benar
- Periksa apakah model dan skema database sejalan

### Validasi Tidak Bekerja

- Periksa apakah FormRequest menggunakan rules dari DTO
- Periksa apakah rules mengembalikan array dengan format yang benar

### Dokumentasi API Tidak Muncul

- Jalankan perintah cache:clear dan config:clear
- Periksa anotasi OpenAPI untuk kesalahan sintaks
- Pastikan endpoint dokumentasi dikonfigurasi dengan benar
