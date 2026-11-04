# Sistem Inventaris & Peminjaman Barang Sekolah

Aplikasi web berbasis **Laravel 13** untuk mengelola inventaris barang milik sekolah (laboratorium, olahraga, elektronik, kebersihan), memfasilitasi pengajuan serta verifikasi peminjaman oleh guru dan siswa, mencatat pengembalian barang, memantau statistik peminjaman, mengirimkan notifikasi email jatuh tempo, dan merekam jejak aktivitas (*audit trail*).

---

## Fitur Utama

- **Autentikasi & Pembagian Role (`admin` & `user`):**
  - **Admin (Petugas Inventaris):** Mengelola kategori, barang, verifikasi pengajuan peminjaman (setujui/tolak), pencatatan pengembalian, pengiriman pengingat email jatuh tempo, manajemen pengguna, dashboard statistik, dan *activity log*.
  - **User (Guru & Siswa):** Melihat katalog barang yang tersedia, mengajukan peminjaman barang, dan memantau riwayat peminjaman milik sendiri dengan tampilan *mobile-first* (dilengkapi navigasi bawah di layar ponsel).
- **Manajemen Master Data:**
  - CRUD **Kategori Barang** (dilengkapi proteksi penghapusan jika kategori masih memiliki barang).
  - CRUD **Data Barang** dengan kode unik, stok, kondisi (`baik`, `rusak ringan`, `rusak berat`), pencarian, filter kategori/kondisi, *soft delete*, dan *pagination* dinamis (`10`, `25`, `50`, `100` data).
- **Alur Transaksi Peminjaman & Pengembalian Otomatis:**
  - Pengajuan peminjaman berstatus awal `menunggu` dengan validasi stok dan tanggal.
  - Persetujuan Admin otomatis mengurangi stok barang secara *atomic* (`DB::transaction` + `lockForUpdate`).
  - Peminjaman aktif yang melewati batas tanggal kembali (`due_date`) otomatis ditandai `terlambat`.
  - Pencatatan pengembalian otomatis mengembalikan stok barang dan memperbarui kondisi barang jika dikembalikan dalam keadaan rusak.
- **Notifikasi Email Jatuh Tempo & Keterlambatan:**
  - Mengirimkan email pengingat untuk peminjaman yang mendekati jatuh tempo (**H-1 / Hari-H**) maupun peringatan untuk peminjaman yang telah melewati jatuh tempo (**Terlambat**), baik secara terjadwal (`loans:send-due-reminders`) maupun manual melalui tombol di halaman Admin.
- **Dashboard Statistik & Audit Trail (*Activity Log*):**
  - Analisis barang paling sering dipinjam, peminjam paling aktif, kategori terpopuler, keterlambatan pengembalian, tren 6 bulan terakhir, kondisi inventaris, serta stok menipis.
  - Pencatatan otomatis setiap aktivitas penting beserta pelaku, waktu, dan alamat IP.

---

## Teknologi yang Digunakan

| Komponen | Teknologi |
|---|---|
| **Backend Framework** | Laravel 13 (PHP 8.3+) |
| **Autentikasi** | Laravel Breeze (Blade + Alpine.js) |
| **Database** | MySQL |
| **UI Template** | Soft UI Dashboard (Bootstrap 5) + Custom Responsive CSS |
| **Asset Bundler** | Vite |

---

## Prasyarat Sistem

Pastikan perangkat Anda telah terpasang:
- **PHP** >= 8.3 (beserta ekstensi `pdo_mysql`, `mbstring`, `openssl`, `xml`, `ctype`, `json`)
- **Composer** >= 2.x
- **Node.js** >= 18.x & **NPM**
- **MySQL** >= 8.0 atau **MariaDB** (misalnya melalui Laragon / XAMPP)

---

## Cara Instalasi

Ikuti langkah-langkah berikut untuk menjalankan proyek di komputer lokal:

### 1. Clone Repository
```bash
git clone <url-repository-anda> sistem-inventaris-sekolah
cd sistem-inventaris-sekolah
```

### 2. Install Dependensi PHP & Frontend
```bash
composer install
npm install
```

### 3. Salin File Environment & Generate App Key
```bash
cp .env.example .env
php artisan key:generate
```
*(Di Windows PowerShell/CMD, Anda juga dapat menggunakan `copy .env.example .env`).*

### 4. Konfigurasi Database & Email di `.env`
Buat database baru di MySQL (contoh: `sistem_inventaris_sekolah`), lalu sesuaikan konfigurasi pada file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_inventaris_sekolah
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi Email (gunakan 'log' untuk pengembangan lokal atau SMTP untuk produksi)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="inventaris@sekolah.test"
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Jalankan Migrasi Database & Seeder
Jalankan perintah berikut untuk membuat seluruh tabel beserta data contoh awal:
```bash
php artisan migrate:fresh --seed
```

### 6. Build Aset Frontend & Jalankan Server
```bash
npm run build
php artisan serve
```
Akses aplikasi melalui browser di alamat: **http://127.0.0.1:8000**

---

## Kredensial Akun Seeder (Testing)

Setelah menjalankan `php artisan migrate:fresh --seed`, Anda dapat langsung login menggunakan akun berikut:

| Nama Pengguna | Email | Password | Role | Keterangan Akses |
|---|---|---|---|---|
| **Admin Inventaris** | `admin@sekolah.test` | `password` | `admin` | Akses penuh (Master Data, Verifikasi Peminjaman, Pengembalian, Statistik, User, Activity Log) |
| **Budi Santoso** | `budi@sekolah.test` | `password` | `user` | Akun contoh Guru (Katalog Barang, Ajukan Peminjaman, Riwayat Peminjaman Saya) |
| **Siti Rahmawati** | `siti@sekolah.test` | `password` | `user` | Akun contoh Siswa (Katalog Barang, Ajukan Peminjaman, Riwayat Peminjaman Saya) |

### Ringkasan Data Awal yang Dibuat Seeder
- **3 Akun Pengguna:** 1 Admin (`admin@sekolah.test`) dan 2 User (`budi@sekolah.test`, `siti@sekolah.test`).
- **4 Kategori Barang:** Alat Laboratorium, Alat Olahraga, Elektronik, dan Alat Kebersihan.
- **12 Barang Inventaris:** Tersebar di 4 kategori dengan variasi stok dan kondisi (`baik`, `rusak ringan`, `rusak berat`).
- **7 Transaksi Peminjaman:** Mencakup seluruh variasi status (`dikembalikan`, `dipinjam`, `terlambat`, `menunggu`, dan `ditolak`).
- **3 Data Pengembalian (`return_logs`):** Sesuai dengan 3 transaksi yang telah berstatus `dikembalikan`.
- **Data Activity Log:** Contoh jejak aktivitas awal di sistem.

---

## Perintah Tambahan & Pengujian

### Mengirim Notifikasi Email Jatuh Tempo / Keterlambatan via CLI
Selain melalui tombol **"Kirim Email Jatuh Tempo"** di halaman Admin, Anda dapat menjalankan perintah Artisan berikut secara manual atau melalui penjadwalan (*scheduler*):
```bash
# Kirim email pengingat (H-1 & Hari-H) serta peringatan keterlambatan
php artisan loans:send-due-reminders

# Opsi tambahan: ubah rentang hari H-N atau paksa kirim ulang di hari yang sama
php artisan loans:send-due-reminders --days=2 --force

# Menjalankan worker penjadwalan otomatis (dijadwalkan setiap hari pukul 07:00)
php artisan schedule:work
```

### Menjalankan Pengujian Otomatis (Automated Tests)
```bash
php artisan test
```
