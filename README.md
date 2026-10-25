# KasirKu

Aplikasi kasir web untuk mengelola kategori, produk, stok, transaksi, pengguna, dan laporan penjualan. Dibuat dengan Laravel 12, PHP 8.2+, Blade, dan Eloquent.

## Persiapan MySQL XAMPP

1. Jalankan MySQL melalui XAMPP Control Panel.
2. Buat database bernama `kasirku` melalui phpMyAdmin.
3. Perbarui hanya variabel database di `.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=kasirku
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   Sesuaikan akun lokal Anda. Jangan menyimpan kredensial produksi di source code.
4. Pastikan target database benar, bersihkan cache konfigurasi, lalu jalankan migrasi:

   ```powershell
   php artisan config:clear
   php artisan migrate
   ```

Migrasi menambah kolom peran dan tabel kategori, produk, penjualan, dan detail transaksi. Migrasi tidak memakai `migrate:fresh` dan tidak menghapus data/tabel yang ada.

### Konfigurasi workspace saat ini

Workspace awal menggunakan `DB_CONNECTION=sqlite`, bukan MySQL. File `.env` tidak diubah oleh implementasi. Untuk beralih ke MySQL, ikuti langkah di atas setelah database `kasirku` dibuat dan koneksi MySQL dipastikan.

## Membuat admin pertama

Jalankan perintah interaktif berikut. Nama, email, dan kata sandi diminta di terminal; kata sandi tidak ditanamkan dalam source code.

```powershell
php artisan kasir:buat-admin
```

Kata sandi minimal 8 karakter. Admin dapat membuat akun kasir atau admin tambahan melalui menu **Pengguna**. Registrasi publik tidak tersedia.

## Menjalankan aplikasi

```powershell
php artisan serve
```

Buka <http://127.0.0.1:8000>, lalu masuk menggunakan akun admin yang dibuat di atas.

## Fitur

- Login/logout, password hashing, dan perlindungan CSRF.
- Peran admin dan kasir dengan pemeriksaan akses pada backend.
- Dashboard statistik dan transaksi terbaru.
- CRUD kategori dan produk, pencarian, filter, pagination, dan peringatan stok rendah.
- Keranjang kasir, metode pembayaran tunai/transfer/QRIS, validasi pembayaran, dan struk siap cetak.
- Checkout database-transactional dengan pemeriksaan ulang stok dan penguncian baris.
- Riwayat stok mencatat stok awal, koreksi manual, dan pengurangan dari transaksi.
- Riwayat transaksi dengan filter tanggal; kasir hanya dapat melihat transaksinya sendiri.
- Laporan periode tanggal, penjualan harian, produk terlaris, dan estimasi laba berdasarkan snapshot harga saat transaksi.
- Snapshot nama produk dan harga pada detail penjualan agar riwayat tidak berubah ketika katalog diperbarui.

## Pengujian

```powershell
php artisan test
```

Pengujian mencakup autentikasi, validasi SKU unik, checkout dan pengurangan stok, pembayaran kurang, pembatasan hak kasir, dan filter rentang tanggal laporan.
