# Dokumentasi Alur Kerja Generator

## Ikhtisar

Sistem generator ini dirancang untuk mengotomatisasi pembuatan arsitektur aplikasi yang lengkap dan skalabel. Generator mengikuti praktik terbaik Laravel dan pola desain untuk membantu Anda membangun aplikasi yang mudah dipelihara dengan berbagai ukuran.

Sistem generator kami menyediakan tiga mode arsitektur:

- **Mode Simple**: Untuk proyek kecil hingga menengah dengan logika bisnis sederhana
- **Mode Standard**: Untuk proyek menengah dengan kompleksitas moderat (default)
- **Mode Advanced**: Untuk proyek besar dan kompleks dengan aturan bisnis yang rumit

## Lapisan Arsitektur

Generator membuat lapisan-lapisan berikut untuk setiap modul:

1. **DTO (Data Transfer Object)** - Merepresentasikan struktur data domain

    - Mengenkapsulasi atribut data
    - Menyediakan metode transformasi
    - Mendefinisikan aturan validasi

2. **Request** - Menangani validasi permintaan HTTP

    - Menggunakan trait bersama untuk aturan validasi
    - Memisahkan API dan validasi Web

3. **Service** - Berisi logika bisnis

    - Mengatur operasi di seluruh aplikasi
    - Mengimplementasikan aturan dan alur kerja domain
    - Menangani upload dan pemrosesan file

4. **Controller** - Memproses permintaan HTTP

    - DB Controller: Operasi domain inti
    - API Controller: Endpoint RESTful
    - Web Controller: Antarmuka web

5. **Resource** - Mentransformasi data untuk respons

    - Base Resource: Atribut bersama
    - API Resource: Representasi khusus API
    - DB Resource: Representasi khusus Web

6. **Repository** (Mode Advanced) - Lapisan akses data
    - Mengabstraksi operasi database
    - Menyediakan strategi caching
    - Memusatkan logika query

## Alur Kerja Penggunaan Generator

### Langkah 1: Inisialisasi Struktur Generator

Langkah pertama yang **WAJIB** dilakukan saat menggunakan generator di project baru adalah menjalankan generator inisialisasi:

```bash
# Inisialisasi struktur dasar untuk generator
php artisan make:generator-init

# Atau, jika ingin menimpa file yang sudah ada
php artisan make:generator-init --force
```

Generator inisialisasi akan:

- Membuat struktur direktori yang diperlukan
- Menyiapkan file dasar yang dibutuhkan
- Mengatur namespace sesuai project Anda
- Membuat stubs untuk generator lainnya

### Langkah 2: Buat Modul Lengkap atau Komponen Individual

Setelah inisialisasi, Anda dapat mulai menggunakan generator untuk membuat modul atau komponen:

```bash
# Membuat modul lengkap
php artisan make:module Product

# Atau membuat komponen individual
php artisan make:dto Product
php artisan make:service Product
# dll
```

### Langkah 3: Tinjau dan Sesuaikan Kode yang Dihasilkan

Setelah generator menghasilkan file-file yang diperlukan:

1. Tinjau kode yang dihasilkan
2. Tambahkan logika bisnis spesifik
3. Konfigurasi routes
4. Uji implementasi

## Perintah Generator

### Perintah Utama

```bash
php artisan make:module {ModelName} [options]
```

Perintah ini mengelola semua generator lainnya untuk membuat modul lengkap.

### Opsi

| Opsi         | Deskripsi                                                         |
| ------------ | ----------------------------------------------------------------- |
| `--simple`   | Menghasilkan arsitektur sederhana untuk proyek kecil              |
| `--standard` | Menghasilkan arsitektur standar (default)                         |
| `--advanced` | Menghasilkan arsitektur lanjutan dengan repository                |
| `--with-web` | Menghasilkan Web Controller dan view                              |
| `--with-api` | Menghasilkan API Controller dan resource (default: true)          |
| `--inertia`  | Menggunakan Inertia.js untuk UI web (default jika with-web diset) |
| `--blade`    | Menggunakan template Blade daripada Inertia.js                    |
| `--prefix=`  | Prefix route untuk resource route                                 |
| `--force`    | Menimpa file yang sudah ada                                       |

### Perintah Individual

Anda juga dapat menggunakan generator individual secara terpisah:

```bash
php artisan make:dto {ModelName}                # Menghasilkan DTO dan RequestDTO
php artisan make:request-trait {DtoName}        # Menghasilkan trait validasi
php artisan make:base-resource {DtoName}        # Menghasilkan base resource
php artisan make:service {ModelName}            # Menghasilkan service dan interface
php artisan make:db-controller {ModelName}      # Menghasilkan DB Controller dan file terkait
php artisan make:web-controller {ModelName}     # Menghasilkan Web Controller
php artisan make:api-classes {DtoName}          # Menghasilkan API Request dan Resource
php artisan make:api-controller {ModelName}     # Menghasilkan API Controller
```

## Mode Arsitektur

### Mode Simple

**Kasus Penggunaan**: Proyek kecil hingga menengah dengan logika bisnis sederhana.

**Fitur**:

- Struktur DTO sederhana dengan validasi minimal
- Implementasi Service dasar
- Akses langsung ke model tanpa lapisan Repository
- Logika controller yang disederhanakan

**File yang Dihasilkan**:

- DTO dengan properti esensial
- Lapisan Service ringan
- Implementasi Controller dasar

**Cocok Untuk**:

- MVP dan prototype
- Aplikasi dengan aturan bisnis sederhana
- Proyek dengan tenggat waktu ketat

**Penggunaan**:

```bash
php artisan make:module Product --simple
```

### Mode Standard (Default)

**Kasus Penggunaan**: Proyek menengah dengan kompleksitas logika bisnis moderat.

**Fitur**:

- Lapisan DTO lengkap untuk representasi data domain
- Lapisan Service komprehensif untuk logika bisnis
- Pemisahan masalah melalui beberapa lapisan
- Validasi dan transformasi lengkap

**File yang Dihasilkan**:

- DTO lengkap dengan validasi
- Lapisan Service dengan kait logika bisnis
- Controller dengan implementasi lengkap

**Cocok Untuk**:

- Sebagian besar aplikasi bisnis
- Proyek yang mungkin berkembang seiring waktu
- Tim dengan beberapa pengembang

**Penggunaan**:

```bash
php artisan make:module Product
# atau secara eksplisit
php artisan make:module Product --standard
```

### Mode Advanced

**Kasus Penggunaan**: Proyek besar, kompleks dengan logika bisnis yang rumit.

**Fitur**:

- Lapisan DTO komprehensif dengan validasi domain
- Pola Repository untuk abstraksi akses data
- Lapisan Service dengan kait logika bisnis lanjutan
- Dukungan arsitektur berbasis event

**File yang Dihasilkan**:

- DTO yang ditingkatkan dengan validasi kompleks
- Kelas Repository untuk akses data
- Lapisan Service dengan kait dan event lanjutan
- Controller dengan implementasi lengkap

**Cocok Untuk**:

- Aplikasi enterprise
- Sistem dengan aturan bisnis kompleks
- Aplikasi volume tinggi yang memerlukan caching
- Proyek dengan banyak titik integrasi

**Penggunaan**:

```bash
php artisan make:module Product --advanced
```

## Contoh Alur Kerja

### Membuat Modul Produk Lengkap

```bash
# Menghasilkan modul produk lengkap dengan antarmuka API dan Web
php artisan make:module Product --with-web

# Untuk sistem produk kompleks dengan fitur lanjutan
php artisan make:module Product --with-web --advanced

# Untuk showcase produk sederhana dengan kompleksitas minimal
php artisan make:module Product --with-web --simple --blade
```

### Setelah Generasi

1. **Tinjau kode yang dihasilkan** dan sesuaikan jika diperlukan
2. **Tambahkan logika bisnis Anda** ke lapisan Service
3. **Konfigurasi route** di `routes/api.php` dan `routes/web.php`
4. **Jalankan tes** untuk memastikan semuanya berfungsi sebagaimana mestinya

## Interaksi Antar Lapisan

```
Web/API Request → Controller → Service → (Repository) → Model → DTO → Resource → Response
```

- **Request** memvalidasi data masuk
- **Controller** mendelegasikan ke Service
- **Service** mengimplementasikan logika bisnis
- **Repository** (mode advanced) menangani akses data
- **Model** merepresentasikan struktur database
- **DTO** mengenkapsulasi transfer data
- **Resource** memformat respons

## Praktik Terbaik

1. **Buat Controller Tipis**

    - Controller hanya memvalidasi input dan mengembalikan respons
    - Delegasikan semua logika bisnis ke Service

2. **Gunakan DTO untuk Transfer Data**

    - DTO harus merepresentasikan konsep domain
    - Gunakan DTO untuk mentransfer data antar lapisan

3. **Layer Service untuk Logika Bisnis**

    - Implementasikan semua aturan bisnis di layer Service
    - Service harus mengatur operasi

4. **Pola Repository (Advanced)**
    - Gunakan Repository untuk mengabstraksi akses data
    - Repository harus menangani semua operasi database

## Memperluas Generator

Anda dapat memperluas generator dengan memodifikasi kelas perintah di `app/Console/Commands`. Setiap generator mengikuti pola yang sama:

1. Parse argumen dan opsi perintah
2. Persiapkan direktori yang diperlukan
3. Hasilkan konten berdasarkan template
4. Tulis file ke lokasi yang sesuai

Untuk menyesuaikan kode yang dihasilkan, modifikasi metode template di kelas generator:

- `generateDtoContent()`
- `generateServiceContent()`
- `generateControllerContent()`
- dll.

## Pemecahan Masalah

### Masalah Umum

1. **Error Class tidak ditemukan**

    - Pastikan model ada sebelum menjalankan generator
    - Periksa impor namespace di file yang dihasilkan

2. **Error validasi**

    - Tinjau aturan validasi di kelas RequestDTO dan Request yang dihasilkan
    - Pastikan aturan kompatibel dengan struktur database Anda

3. **Konflik route**
    - Periksa deklarasi route duplikat di file route Anda
    - Gunakan nama route dan prefix yang unik

### Mendapatkan Bantuan

Jika Anda mengalami masalah dengan generator, periksa:

1. Dokumentasi Laravel: https://laravel.com/docs
2. Dokumentasi proyek: docs/
3. Buka masalah di repositori proyek

## Memindahkan Generator ke Project Lain

Jika Anda ingin menggunakan generator ini pada project Laravel lain, berikut adalah panduan untuk memindahkan komponen-komponen yang diperlukan:

### Komponen yang Perlu Dipindahkan

1. **File Generator Commands**

    - Pindahkan semua file generator dari `app/Console/Commands/` yang berhubungan dengan generator:
        ```
        app/Console/Commands/InitGeneratorStructure.php (PRIORITAS UTAMA)
        app/Console/Commands/GenerateDtoFromModel.php
        app/Console/Commands/GenerateApiClassesFromDto.php
        app/Console/Commands/GenerateApiController.php
        app/Console/Commands/GenerateFullModuleFromModel.php
        app/Console/Commands/GenerateServiceFromModel.php
        app/Console/Commands/GenerateDbControllerFromModel.php
        app/Console/Commands/GenerateWebControllerFromModel.php
        app/Console/Commands/GenerateRequestTraitFromDto.php
        app/Console/Commands/GenerateBaseResourceFromDto.php
        ```

2. **Struktur Stubs**
    - Pastikan directory stubs ikut disalin:
        ```
        app/Console/Commands/stubs/generator/
        ```

### Langkah-langkah Pemindahan

1. **Salin File Generator ke Project Tujuan**

    ```bash
    # Di project sumber, perbarui stubs jika diperlukan
    php artisan make:generator-init

    # Copy file generator command dan stubs ke project baru
    cp app/Console/Commands/InitGeneratorStructure.php /path/to/target-project/app/Console/Commands/
    cp -r app/Console/Commands/stubs/generator /path/to/target-project/app/Console/Commands/stubs/
    ```

2. **Jalankan Generator Inisialisasi di Project Tujuan**

    ```bash
    cd /path/to/target-project
    php artisan make:generator-init
    ```

3. **Verifikasi Instalasi**

    ```bash
    php artisan list | grep make:
    ```

### Tips Pemindahan

1. **Gunakan Composer Package**

    Cara terbaik untuk mendistribusikan generator adalah dengan membuat package Composer terpisah. Ini memudahkan pemeliharaan dan update di berbagai project.

    ```bash
    # Contoh instalasi jika dibuat sebagai package
    composer require your-vendor/laravel-dto-generator
    ```

2. **Gunakan Git Submodule**

    Alternatif lain adalah menggunakan Git submodule untuk menyinkronkan generator di berbagai project.

3. **Pertahankan Konsistensi**

    Pastikan versi generator yang digunakan di semua project selalu konsisten untuk menghindari perbedaan implementasi.

### Checklist Migrasi

- [ ] File `InitGeneratorStructure.php` telah dipindahkan
- [ ] Directory stubs telah dipindahkan
- [ ] Generator inisialisasi telah dijalankan di project tujuan
- [ ] File generator commands lainnya telah dipindahkan (opsional, akan disalin oleh init)
- [ ] Generator telah diuji di project tujuan
