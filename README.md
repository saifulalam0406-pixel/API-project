# Perpustakaan API

REST API Laravel 12 untuk akun pengguna dan pengelolaan data buku. Autentikasi memakai token Laravel Sanctum; dokumentasi interaktif dibuat dengan OpenAPI 3 dan L5-Swagger.

## Persyaratan

- PHP 8.3 atau lebih baru dengan ekstensi `mbstring`, `openssl`, dan `pdo_sqlite`
- Composer 2

## Menjalankan secara lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve
```

Server berjalan di `http://localhost:8000`. `migrate:fresh --seed` menghapus lalu membuat ulang semua tabel dan data; gunakan hanya pada database lokal/pengujian, jangan pada produksi.

Akun contoh dari seeder:

- Email: `admin@example.com`
- Password: `password`

Seeder juga membuat 20 buku contoh. Jangan gunakan kredensial demo ini di lingkungan produksi.

## Endpoint

Semua endpoint mengharapkan header `Accept: application/json`.

| Method | Endpoint | Keterangan | Autentikasi |
|---|---|---|---|
| POST | `/api/register` | Daftar akun dan menerima token | Tidak |
| POST | `/api/login` | Login dan menerima token; dibatasi 6 percobaan per menit | Tidak |
| GET | `/api/me` | Profil pengguna saat ini | Ya |
| POST | `/api/logout` | Cabut token yang sedang digunakan | Ya |
| GET | `/api/books` | Daftar buku, pencarian judul/penulis, dan paginasi | Ya |
| POST | `/api/books` | Tambah buku | Ya |
| GET | `/api/books/{book}` | Detail buku | Ya |
| PUT/PATCH | `/api/books/{book}` | Ubah semua/sebagian data buku | Ya |
| DELETE | `/api/books/{book}` | Hapus buku | Ya |

Kirim token pada header `Authorization: Bearer <token>`. Untuk pencarian, gunakan `?search=laravel`; ukuran halaman dapat diatur dengan `?per_page=10&page=1` (maksimal 100).

## Dokumentasi dan pengujian

- Swagger UI: `http://localhost:8000/api/documentation`
- Spesifikasi OpenAPI JSON: `http://localhost:8000/docs`
- Generate ulang dokumentasi: `php artisan l5-swagger:generate`
- Jalankan tes otomatis: `php artisan test`

Di Swagger UI, login melalui `POST /api/login`, salin token, lalu tekan **Authorize** dan masukkan token tersebut. Swagger akan mengirim header Bearer secara otomatis.

Tes menggunakan SQLite in-memory, sehingga tidak mengubah database lokal.

## Catatan produksi

- Set `L5_SWAGGER_GENERATE_ALWAYS=false`, lalu generate dokumentasi saat deployment.
- Lindungi atau nonaktifkan akses publik ke `/api/documentation`.
- Ganti `L5_SWAGGER_CONST_HOST` dengan URL API produksi.
- Atur masa berlaku token pada `config/sanctum.php`.
- Gunakan database produksi seperti MySQL/PostgreSQL dan jangan menjalankan `migrate:fresh`.
