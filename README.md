# KasirKu

Aplikasi kasir berbasis Laravel 12 dan PHP 8.2+ untuk mengelola pengguna, kategori, produk, transaksi, persediaan, dan laporan penjualan.

## Menjalankan secara lokal

Workspace ini menggunakan SQLite. Pastikan dependensi Composer terpasang dan migrasi lokal sudah diterapkan, lalu jalankan:

```powershell
php artisan serve
```

Buka <http://127.0.0.1:8000>. Untuk membuat admin pertama secara interaktif:

```powershell
php artisan kasir:buat-admin
```

Perintah tersebut meminta nama, email, dan kata sandi secara lokal; kata sandi tidak perlu dikirim melalui chat atau disimpan di source code.

## Deploy ke Render dengan Docker

Image produksi memakai PHP 8.3-FPM, Nginx, Supervisor, Composer, dan Node.js untuk membangun aset Vite. Container menerima port dari environment `PORT` yang disediakan Render (default lokal image: `10000`). Database produksi menggunakan PostgreSQL; database SQLite lokal tidak disertakan dalam image.

### 1. Siapkan PostgreSQL di Render

1. Push proyek ke repositori GitHub.
2. Di Render, buat **PostgreSQL**. Pilih region yang sama dengan Web Service.
3. Buat **Web Service** dari repositori ini dan pilih **Docker** sebagai runtime.
4. Pilih **Dockerfile Path** `./Dockerfile` dan **Docker Build Context Directory** `.`.
5. Jika Web Service memiliki pengaturan health check, set path ke `/up`.
6. Salin **Internal Database URL** dari PostgreSQL Render. Gunakan URL internal, bukan URL eksternal, untuk koneksi antarlayanan Render.

### 2. Atur environment Web Service

Set variabel berikut pada halaman **Environment** Web Service. Jangan menaruh nilainya di repository atau Dockerfile.

| Key | Nilai |
| --- | --- |
| `APP_NAME` | `KASIRKU` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Kunci baru yang dibuat dengan `php artisan key:generate --show`; simpan hanya di Render |
| `APP_URL` | URL HTTPS Web Service Render, misalnya `https://nama-service.onrender.com` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `info` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Internal Database URL PostgreSQL Render |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |

Render mengatur `PORT` untuk proses Web Service; tidak perlu memasukkan port database ke `PORT`. Jika menggunakan pengaturan database terpisah alih-alih `DB_URL`, isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` dari detail PostgreSQL Render. Jangan commit nilai kredensial.

Buat `APP_KEY` satu kali dari terminal lokal yang memiliki dependensi Composer:

```powershell
php artisan key:generate --show
```

Perintah tersebut hanya menampilkan kunci untuk disalin ke environment Render. Jangan menaruh kunci itu di Git, README, atau Dockerfile. Pertahankan nilai `APP_KEY` yang sama pada redeploy agar sesi dan data terenkripsi tetap dapat dibaca.

Konfigurasi Laravel mempercayai header forwarded untuk alamat klien, port, dan protokol HTTPS dari reverse proxy Render. Header host tidak dipercaya; tetapkan `APP_URL` ke URL HTTPS publik agar URL console dan aset memakai host yang benar. Cookie sesi produksi disetel hanya untuk HTTPS.

### 3. Deploy dan jalankan migrasi dengan aman

Deploy Web Service setelah environment dan database siap. Periksa log build/startup; container tidak menjalankan migrasi atau seeder secara otomatis.

Setelah Service dapat mengakses PostgreSQL, jalankan perintah berikut melalui Shell Web Service Render (jika tersedia pada plan Anda) atau **Pre-Deploy Command** Web Service:

```sh
php artisan migrate --force
```

Periksa bahwa perintah selesai tanpa error sebelum menggunakan aplikasi. Perintah ini menerapkan migrasi yang belum berjalan dan tidak menghapus tabel/data. Jangan taruh perintah migrasi di Docker entrypoint atau startup command, karena startup dapat berjalan berulang kali. Jangan gunakan `migrate:fresh`, `db:wipe`, atau menjalankan seeder untuk deployment ini.

### 4. Buat akun admin produksi

Setelah migrasi berhasil, jalankan perintah interaktif ini dari Shell Web Service yang menyediakan terminal interaktif:

```sh
php artisan kasir:buat-admin
```

Isi nama dan email di shell tersebut, lalu masukkan kata sandi minimal 8 karakter pada prompt tersembunyi. Jangan menyimpan atau mengirim kata sandi melalui GitHub, environment build, log, atau chat. Jika akun admin sudah tersedia, perintah akan menolak membuat admin pertama lagi; gunakan pengelolaan akun setelah login.

## Catatan operasional

- PostgreSQL Render menyimpan data produksi; container tidak menyimpan database aplikasi sebagai file.
- File lokal `.env`, database SQLite, `vendor`, `node_modules`, log lokal, dan file tes dikecualikan dari Docker build context.
- Laravel menulis log ke `stderr` agar tersedia pada log Web Service Render.
- Migrasi produksi harus dijalankan secara eksplisit setelah database terkonfigurasi, bukan saat image dibangun atau setiap container mulai.
- File deployment yang disediakan: `Dockerfile`, `.dockerignore`, dan konfigurasi di `docker/`.
- Docker perlu tersedia untuk membangun atau menjalankan image secara lokal.

## Fitur

- Login/logout dengan peran admin dan kasir.
- CRUD kategori dan produk, pencarian, filter, pagination, dan peringatan stok rendah.
- Keranjang kasir, pembayaran tunai/transfer/QRIS, dan struk siap cetak.
- Checkout transaksional dengan pemeriksaan ulang stok.
- Riwayat stok, transaksi, laporan penjualan, serta estimasi laba.

## Pengujian

```powershell
php artisan test
vendor\bin\pint --test
```
