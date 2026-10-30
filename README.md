# NEXA – Next-generation Asset Management

Aplikasi web manajemen aset untuk usaha jual beli dan restorasi motor. Proyek Kolaboratif Kelompok B, Teknik Informatika FT UMJ 2026.

| Bagian | Teknologi |
| --- | --- |
| Frontend | Vue.js 3 (Vite), Vue Router, Bootstrap 5, Bootstrap Icons |
| Backend | PHP 7.3, CodeIgniter 3.1.13 (REST API JSON) |
| Database | SQLite (satu file `backend/writable/nexa.db`) |
| Pelaporan (Tahap 5) | PhpSpreadsheet (Excel), Dompdf (PDF) |
| Lingkungan opsional | Docker + Docker Compose (PHP 8.2, Node 20) |

**Status saat ini: kode Tahap 1–4 dari 5 sudah selesai** (target Mid-Project Review M8, sekitar 14 November 2026). Pembagian tahap, tugas, dan kriteria selesainya ada di [`PANDUAN-TAHAP.md`](PANDUAN-TAHAP.md). Baca file itu sebelum mulai mengerjakan.

Ada dua cara menjalankan aplikasi. Pilih salah satu.

| Cara | Cocok untuk | Yang perlu dipasang |
| --- | --- | --- |
| **A. Docker** | PC yang tidak punya PHP, atau ingin lingkungan yang sama persis di semua anggota | Docker Desktop saja |
| **B. Langsung (lokal)** | PC yang sudah ada XAMPP dengan PHP 7.3 | PHP 7.3, Composer, Node.js |

Keduanya memakai folder kode yang sama, jadi anggota tim boleh memakai cara berbeda.

## Cara A: Docker (tanpa PHP)

1. Pasang **Docker Desktop** dari https://www.docker.com/products/docker-desktop.
   - Di Windows, ikuti petunjuk mengaktifkan WSL 2 kalau diminta, lalu restart.
2. Buka Docker Desktop dan tunggu sampai statusnya *running*.
3. Klik dua kali `docker-start.bat`. Di macOS/Linux, jalankan `./docker-start.sh`.
4. Tunggu sampai browser terbuka di http://localhost:5173.
   - Run pertama butuh 3–10 menit, karena mengunduh PHP 7.3, CodeIgniter 3, dan paket npm.
   - Run berikutnya hanya beberapa detik.

Perintah yang sering dipakai (jalankan di folder `nexa`):

| Keperluan | Perintah |
| --- | --- |
| Menghentikan aplikasi | `docker-stop.bat` atau `docker compose down` |
| Melihat log/error | `docker compose logs -f backend` (atau `frontend`) |
| Reset database ke data dummy | `docker-reset-data.bat` |
| Menjalankan perintah CLI CodeIgniter | `docker compose exec backend php index.php tools migrate` |
| Menambah paket composer (Tahap 5) | `docker compose exec backend composer require dompdf/dompdf` |
| Menambah paket npm | `docker compose exec frontend npm install nama-paket` |
| Pasang ulang semua dari nol | `docker compose down -v` lalu `docker-start.bat` |

Catatan:
- **Edit kode seperti biasa.** Folder `backend` dan `frontend` terhubung langsung ke container. Perubahan Vue langsung terlihat di browser, dan perubahan PHP berlaku di request berikutnya.
- **File database** tetap berada di `backend/writable/nexa.db` di komputer Anda, jadi bisa dibuka dengan DB Browser for SQLite.
- **Di Linux,** file yang dibuat container (misalnya `nexa.db`) dimiliki root. Kalau perlu, jalankan `sudo chown -R $USER backend`.
- **Kalau port 8080 atau 5173 sudah dipakai** aplikasi lain (misalnya XAMPP), hentikan aplikasi itu dulu, atau ubah bagian `ports` di `docker-compose.yml`.

## Cara B: Langsung tanpa Docker

### Yang perlu dipasang

1. **PHP 7.3.** Paling mudah lewat XAMPP versi 7.3.x (tersedia di halaman *More Downloads* XAMPP). Pastikan folder PHP masuk PATH (cek: `php -v`).
   - PHP 7.4 juga bisa.
   - PHP 8 tidak disarankan, karena CodeIgniter 3 memunculkan pesan *deprecated* yang bisa merusak respons JSON.
2. **Ekstensi PHP `sqlite3` aktif.** Buka `php.ini` (di XAMPP: `C:\xampp\php\php.ini`), hapus titik koma di depan `extension=sqlite3`, lalu simpan. Cek dengan `php -m`.
3. **Composer** dari https://getcomposer.org
4. **Node.js versi LTS** dari https://nodejs.org (sudah termasuk npm).

### Menjalankan pertama kali

**Windows:** klik dua kali `setup.bat`, tunggu selesai, lalu klik dua kali `start.bat`.

**macOS/Linux:** `./setup.sh` lalu `./start.sh`.

Browser akan membuka http://localhost:5173. Masuk dengan salah satu akun berikut.

| Role | Username | Password |
| --- | --- | --- |
| Administrator | `admin` | `admin123` |
| Pemilik Usaha | `pemilik` | `pemilik123` |
| Staff Operasional | `operasional` | `ops123` |
| Staff Penjualan | `penjualan` | `sales123` |
| Staff Operasional (mekanik) | `mekanik` | `mekanik123` |

#### Langkah manual (kalau tidak memakai setup.bat)

```bash
# 1. Pasang CodeIgniter 3, lalu salin ke folder backend tanpa menimpa file NEXA
composer create-project codeigniter/framework _ci3 3.1.13 --no-dev
#    Windows:  robocopy _ci3 backend /E /XC /XN /XO
#    mac/linux: cp -Rn _ci3/. backend/
#    lalu hapus folder _ci3

# 2. Buat tabel dan isi data dummy
cd backend
php index.php tools migrate
php index.php tools seed
php -S localhost:8080 server.php   # API di http://localhost:8080

# 3. Frontend (terminal lain)
cd frontend
npm install
npm run dev                # aplikasi di http://localhost:5173
```

## Perintah yang sering dipakai

| Keperluan | Perintah (di folder `backend`) |
| --- | --- |
| Buat/upgrade tabel | `php index.php tools migrate` |
| Reset database ke data dummy awal | `php index.php tools reset` |
| Isi ulang data dummy saja | `php index.php tools seed` |
| Lihat semua route API | buka `application/config/routes.php` |
| Ubah data dummy | edit `tools/generate_dummy.py`, jalankan `python tools/generate_dummy.py`, lalu seed ulang |

Untuk melihat isi database secara langsung, buka `backend/writable/nexa.db` dengan **DB Browser for SQLite**. Tanpa menjalankan aplikasi pun bisa: impor `database/nexa_dump.sql` di DB Browser (File > Import > Database from SQL file).

## Struktur folder

```
nexa/
├── PANDUAN-TAHAP.md          panduan pengerjaan 5 tahap + serah terima
├── setup.bat / setup.sh      instalasi otomatis
├── start.bat / start.sh      menjalankan backend + frontend
├── docker-compose.yml        menjalankan semuanya lewat Docker
├── docker-start.bat / .sh    start Docker + buka browser
├── docker-stop.bat, docker-reset-data.bat
├── docker/                   Dockerfile & skrip start backend
├── backend/                  CodeIgniter 3 (folder system/ & index.php ditambahkan oleh setup)
│   ├── server.php            router untuk php -S (pengganti Apache/.htaccess)
│   ├── writable/nexa.db      database SQLite (dibuat otomatis)
│   └── application/
│       ├── config/nexa.php   status lifecycle, hak akses per role, checklist inspeksi
│       ├── config/routes.php daftar endpoint /api (Tahap 2-5 sudah disiapkan sebagai komentar)
│       ├── config/database.php  koneksi SQLite
│       ├── core/MY_Controller.php  Api_Controller: cek login & role, helper respons JSON
│       ├── controllers/api/  Auth, Meta, Dashboard, Motors, Documents, Inspections, Costs, Inventory
│       ├── libraries/Health_score.php  rumus Asset Health Score
│       ├── controllers/Tools.php  perintah CLI: migrate, seed, reset
│       ├── models/           model per tabel
│       ├── migrations/       struktur seluruh tabel sesuai ERD
│       └── database/dummy.json  data dummy
├── frontend/                 Vue 3 + Vite
│   └── src/
│       ├── api.js            pemanggil API (otomatis kirim token)
│       ├── router.js         halaman + penjaga role
│       ├── stores/auth.js    login, logout, hak akses
│       ├── utils/            konstanta & format rupiah/tanggal
│       ├── components/       layout, modal, gauge Health Score, jalur status, timeline
│       ├── components/forms/ form motor, status, dokumen, biaya, barang masuk/keluar
│       └── views/            Login, Dashboard, Kendaraan, Detail motor, Inspeksi, Biaya,
│                             Inventaris, Placeholder (menu Tahap 5)
├── database/
│   ├── schema.sql            skema SQL (cerminan migration)
│   └── nexa_dump.sql         skema + data dummy, untuk DB Browser
├── tools/generate_dummy.py   sumber data dummy
└── referensi/prototype-nexa.html   contoh tampilan semua fitur (buka langsung di browser)
```

## Kalau ada masalah

| Gejala | Penyebab dan solusi |
| --- | --- |
| `Class 'SQLite3' not found` atau `Unable to connect to your database server` | Ekstensi sqlite3 belum aktif di `php.ini`. |
| Muncul pesan `Deprecated` / `A PHP Error was encountered` di respons API | Versi PHP terlalu baru untuk CodeIgniter 3. Pakai PHP 7.3 (XAMPP 7.3) atau Docker. |
| Halaman login menampilkan "Tidak bisa terhubung ke server" | Backend belum jalan. Jalankan `start.bat`, atau `php -S localhost:8080 server.php` di folder `backend`. |
| Login selalu gagal | Data dummy belum diisi. Jalankan `php index.php tools seed` di folder `backend`. |
| Docker: halaman tidak terbuka setelah lama menunggu | Lihat `docker compose logs backend`. Biasanya koneksi internet terputus saat mengunduh. Jalankan `docker-start.bat` lagi. |
| Docker: `exec /usr/local/bin/nexa-entrypoint: no such file` | Jalankan `docker compose build --no-cache backend`. |
| Port 8080 sudah dipakai | Jalankan `php -S localhost:8081 server.php`, lalu ubah `target` di `frontend/vite.config.js` ke port yang sama. |
