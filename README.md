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

## Deploy ke DockHosting dengan Docker

Image produksi menggunakan PHP 8.3-FPM, Nginx, Supervisor, Composer, dan Node.js untuk membangun aset Vite. Nginx mendengarkan port `PORT` yang diberikan DockHosting; MySQL 8 menjadi database produksi. File `.env`, database SQLite lokal, dan dependensi development dikecualikan dari Docker build context.

Panduan ini mengikuti [Quickstart DockHosting](https://docs.dockhosting.dev/docs/quickstart), [panduan Docker](https://docs.dockhosting.dev/docs/deploying/docker), [panduan Laravel](https://docs.dockhosting.dev/docs/deploying/laravel), dan [dokumentasi database](https://docs.dockhosting.dev/docs/databases).

### 1. Buat akun dan instal CLI

1. Daftar di [app.dockhosting.dev/register](https://app.dockhosting.dev/register). Dokumentasi menyebut paket Free tidak memerlukan kartu kredit.
2. Di PowerShell Windows, gunakan perintah installer resmi dari Quickstart:

   ```powershell
   irm https://get.dockhosting.dev/install.ps1 | iex
   ```

3. Buka terminal baru dan verifikasi instalasi:

   ```powershell
   dock --version
   dock --help
   ```

Ikuti proses login resmi melalui CLI/dashboard bila diminta. Jangan kirim password atau token ke chat dan jangan menyimpan token di source code.

### 2. Hubungkan database MySQL

Attach database MySQL 8 yang sudah dibuat ke project aplikasi. DockHosting menyediakan connection string melalui private network dan memasukkan variabel koneksi ke environment project. Gunakan nama variabel yang tercantum di tab **Connection** database. Untuk project ini, tab tersebut menampilkan `DATABASE_URL` dengan format `mysql://...` serta `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. `DB_CONNECTION` harus bernilai `mysql`; image menetapkannya sebagai default, kecuali jika ditimpa oleh environment project.

Di **Project Settings → Environment**, atur:

| Key | Nilai |
| --- | --- |
| `APP_NAME` | `KASIRKU` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Kunci baru yang dibuat lokal dengan `php artisan key:generate --show` |
| `APP_URL` | URL HTTPS project DockHosting |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `info` |
| `DB_CONNECTION` | `mysql` |
| `DATABASE_URL` | Connection string MySQL privat `mysql://...` dari DockHosting; jangan salin nilainya ke Git atau chat |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |

`DATABASE_URL` dibaca langsung oleh koneksi MySQL Laravel; konfigurasi juga menerima `DB_URL` bila Anda sengaja mengaturnya sendiri. Jangan membuat salinan kredensial di source code. Jangan menaruh URL, password, `APP_KEY`, atau rahasia lain dalam Git, Dockerfile, README, maupun chat. Buat `APP_KEY` satu kali dan pertahankan nilainya pada redeploy.

Konfigurasi container menetapkan `DB_CONNECTION=mysql`, mensyaratkan `APP_KEY` dan konfigurasi koneksi MySQL, menggunakan `PORT` dari platform, dan mengirim log Laravel/Nginx ke output container. Laravel mempercayai header forwarded protokol, port, serta alamat klien untuk reverse proxy; `APP_URL` harus memakai alamat HTTPS project.

### 3. Deploy dan migrasikan database

Dari root repository ini setelah akun, project, database MySQL, dan environment siap:

```powershell
dock deploy
```

Periksa log deployment. Setelah container berhasil berjalan dan database terhubung, jalankan migrasi melalui one-off shell DockHosting atau deploy step jika tersedia:

```sh
php artisan migrate --force
```

Jangan jalankan migrasi sebelum database produksi terkonfigurasi dan dapat diakses. Container tidak menjalankan migrasi atau seeder otomatis. Perintah migrasi di atas menerapkan perubahan yang tertunda; jangan gunakan `migrate:fresh`, `db:wipe`, atau seeder pada deployment ini.

Setelah migrasi sukses, buat admin pertama melalui shell interaktif yang terhubung ke environment project:

```sh
php artisan kasir:buat-admin
```

Isi nama dan email pada shell, lalu masukkan kata sandi minimal 8 karakter saat prompt tersembunyi. Jangan menaruh kata sandi dalam source code, log, atau chat. Bila admin sudah ada, perintah akan menolak membuat admin pertama duplikat.

Untuk melihat log build/runtime:

```powershell
dock logs --follow
```

### Paket gratis dan penyimpanan

Dokumentasi DockHosting menyebut paket Free menyediakan hingga 3 project, 1 database, dan 1 GB RAM per aplikasi; aplikasi tidur ketika idle dan aktif kembali saat menerima request pertama. URL HTTPS subdomain DockHosting tersedia tanpa domain kustom. Database dikelola terpisah dan dapat di-attach lewat private network. Periksa [pricing resmi](https://docs.dockhosting.dev/docs/pricing-and-billing) untuk ketentuan terkini sebelum deployment.

Database, sesi, cache, dan queue aplikasi ini memakai MySQL. SQLite tetap digunakan untuk development dan pengujian lokal. Container bersifat disposable; file di filesystem container bukan penyimpanan permanen, sehingga file unggahan pengguna (jika kelak ditambahkan) memerlukan volume persisten atau object storage.

File deployment: `Dockerfile`, `.dockerignore`, dan konfigurasi di `docker/`. Docker lokal diperlukan hanya jika ingin membangun/menguji image di komputer sendiri.

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
