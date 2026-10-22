# NEXA – Next-generation Asset Management

Aplikasi web manajemen aset untuk usaha jual beli dan restorasi motor. Proyek Kolaboratif Kelompok B, Teknik Informatika FT UMJ 2026.

| Bagian | Teknologi |
| --- | --- |
| Frontend | Vue.js 3 (Vite), Vue Router, Bootstrap 5, Bootstrap Icons |
| Backend | PHP 8.1+, CodeIgniter 4 (REST API JSON) |
| Database | SQLite (satu file `backend/writable/nexa.db`) |
| Pelaporan (Tahap 5) | PhpSpreadsheet (Excel), Dompdf (PDF) |

**Status saat ini: Tahap 1 dari 5 selesai.** Pembagian tahap, tugas, dan kriteria selesainya ada di [`PANDUAN-TAHAP.md`](PANDUAN-TAHAP.md). Baca file itu sebelum mulai mengerjakan.

## Yang perlu dipasang

1. **PHP 8.1 atau lebih baru.** Bisa dari XAMPP. Pastikan folder PHP masuk PATH (cek: `php -v`).
2. **Ekstensi PHP `sqlite3` dan `intl` aktif.** Buka `php.ini` (di XAMPP: `C:\xampp\php\php.ini`), hapus titik koma di depan `extension=sqlite3` dan `extension=intl`, simpan. Cek dengan `php -m`.
3. **Composer** dari https://getcomposer.org
4. **Node.js versi LTS** dari https://nodejs.org (sudah termasuk npm).

## Menjalankan pertama kali

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

### Langkah manual (kalau tidak memakai setup.bat)

```bash
# 1. Pasang framework CodeIgniter 4, lalu salin ke folder backend tanpa menimpa file NEXA
composer create-project codeigniter4/appstarter _ci4
#    Windows:  robocopy _ci4 backend /E /XC /XN /XO
#    mac/linux: cp -Rn _ci4/. backend/
#    lalu hapus folder _ci4

# 2. Buat tabel dan isi data dummy
cd backend
php spark migrate
php spark db:seed DatabaseSeeder
php spark serve            # API di http://localhost:8080

# 3. Frontend (terminal lain)
cd frontend
npm install
npm run dev                # aplikasi di http://localhost:5173
```

## Perintah yang sering dipakai

| Keperluan | Perintah (di folder `backend`) |
| --- | --- |
| Reset database ke data dummy awal | `php spark migrate:refresh` lalu `php spark db:seed DatabaseSeeder` |
| Isi ulang data dummy saja | `php spark db:seed DatabaseSeeder` |
| Lihat semua route API | `php spark routes` |
| Ubah data dummy | edit `tools/generate_dummy.py`, jalankan `python tools/generate_dummy.py`, lalu seed ulang |

Untuk melihat isi database secara langsung, buka `backend/writable/nexa.db` dengan **DB Browser for SQLite**. Tanpa menjalankan aplikasi pun bisa: impor `database/nexa_dump.sql` di DB Browser (File > Import > Database from SQL file).

## Struktur folder

```
nexa/
├── PANDUAN-TAHAP.md          panduan pengerjaan 5 tahap + serah terima
├── setup.bat / setup.sh      instalasi otomatis
├── start.bat / start.sh      menjalankan backend + frontend
├── backend/                  CodeIgniter 4 (file framework ditambahkan oleh setup)
│   ├── .env                  konfigurasi: SQLite, base URL
│   └── app/
│       ├── Config/Nexa.php   status lifecycle, hak akses per role, checklist inspeksi
│       ├── Config/Routes.php daftar endpoint /api (Tahap 2-5 sudah disiapkan sebagai komentar)
│       ├── Controllers/Api/  controller REST (BaseApiController berisi pengecekan login & role)
│       ├── Models/           model per tabel
│       └── Database/
│           ├── Migrations/   struktur seluruh tabel sesuai ERD
│           └── Seeds/        DatabaseSeeder + data/dummy.json
├── frontend/                 Vue 3 + Vite
│   └── src/
│       ├── api.js            pemanggil API (otomatis kirim token)
│       ├── router.js         halaman + penjaga role
│       ├── stores/auth.js    login, logout, hak akses
│       ├── utils/            konstanta & format rupiah/tanggal
│       ├── components/       AppLayout, StatusBadge
│       └── views/            Login, Dashboard, Placeholder (menu yang belum dikerjakan)
├── database/
│   ├── schema.sql            skema SQL (cerminan migration)
│   └── nexa_dump.sql         skema + data dummy, untuk DB Browser
├── tools/generate_dummy.py   sumber data dummy
└── referensi/prototype-nexa.html   contoh tampilan semua fitur (buka langsung di browser)
```

## Kalau ada masalah

| Gejala | Penyebab dan solusi |
| --- | --- |
| `Class "SQLite3" not found` atau `Unable to connect to the database` | Ekstensi sqlite3 belum aktif di `php.ini`. |
| `The framework needs the following extension(s) installed and loaded: intl` | Aktifkan `extension=intl` di `php.ini`. |
| Halaman login menampilkan "Tidak bisa terhubung ke server" | Backend belum jalan. Jalankan `php spark serve` di folder `backend`. |
| Login selalu gagal | Data dummy belum diisi. Jalankan `php spark db:seed DatabaseSeeder`. |
| Port 8080 sudah dipakai | `php spark serve --port 8081`, lalu ubah `target` di `frontend/vite.config.js` ke port yang sama. |

## Cara tercepat (Windows)

Klik dua kali **`MULAI-NEXA.bat`**. Pertama kali akan mengunduh PHP, Composer, dan Node.js otomatis ke `tools/runtime` (butuh internet, sekitar 5-10 menit), lalu setup dan menjalankan aplikasi. Berikutnya langsung jalan dalam beberapa detik. PATH Windows tidak diubah.
