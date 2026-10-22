# Panduan Pengerjaan NEXA per Tahap

Pengembangan NEXA dibagi menjadi 5 tahap agar beban kerja tidak menumpuk dan setiap tahap bisa diuji sebelum lanjut. Setiap tahap menghasilkan fitur yang bisa didemokan. Siapa pun yang melanjutkan cukup membaca bagian tahapnya di dokumen ini, ditambah bagian "Aturan main" yang berlaku untuk semua tahap.

## Ringkasan

| Tahap | Isi | Usulan PJ (Bab 4.2) | Usulan minggu | Status |
| --- | --- | --- | --- | --- |
| 1 | Fondasi: database + data dummy, login 4 role, layout, dashboard dasar | Mulyo (database), Alifya (backend), Firmansyah (layout) | M3 | **Selesai, tinggal direview** |
| 2 | Data kendaraan, dokumen, Asset Timeline | Backend: Alifya. Frontend: Firmansyah. Validasi: Akmal | M4 | Belum |
| 3 | Inspeksi + Asset Health Score, pencatatan biaya | Backend: Mulyo. Frontend: Firmansyah. Validasi checklist: Akmal | M5–M6 | Belum |
| 4 | Inventaris & sparepart, stok minimum | Backend: Alifya. Frontend: Firmansyah. Validasi & uji: Kemal | M6–M7 | Belum |
| 5 | Penjualan, laporan PDF/Excel, manajemen pengguna, dashboard lengkap, UAT | Backend: Alifya & Mulyo. Frontend: Firmansyah. Uji & user manual: Kemal. UAT: Akmal | M8–M10 | Belum |

Mid-Project Review di minggu ke-8 (Bab 3.1) idealnya dilakukan setelah Tahap 4 selesai, sehingga semua fungsi inti sudah berjalan dan Tahap 5 menjadi finalisasi.

Tampilan akhir setiap halaman sudah ada di `referensi/prototype-nexa.html`. Buka file itu di browser dan login dengan akun mana pun. Prototipe itu adalah acuan tampilan dan alur, bukan kode yang dipakai.

---

## Aturan main untuk semua tahap

### Alur kerja Git

1. Buat branch per tahap dari `main`, misalnya `tahap-2-kendaraan`.
2. Commit kecil dan sering, dengan pesan yang jelas, misalnya `tahap2: endpoint daftar motor`.
3. Sebelum merge ke `main`: jalankan `php spark migrate:refresh && php spark db:seed DatabaseSeeder`, lalu jalankan semua uji di bagian tahap Anda.
4. Setelah merge, isi tabel **Catatan serah terima** di bagian bawah dokumen ini.

### Backend (CodeIgniter 4)

- **Controller API** dibuat di `app/Controllers/Api/` dan wajib extends `BaseApiController`.
- **Cek login dan hak akses** di awal setiap method:
  ```php
  if ($deny = $this->guard('motor.edit')) {
      return $deny;
  }
  ```
  Kunci hak akses (`motor.edit`, `cost.view`, dll.) didaftarkan di `app/Config/Nexa.php`. Kalau menambah kunci baru, tambahkan juga di sana.
- **Format respons.**
  - Sukses memakai `$this->respond($data)` atau `$this->respondCreated($data)`.
  - Error validasi memakai `$this->failValidationErrors(['field' => 'pesan'])`.
  - Data tidak ditemukan memakai `$this->failNotFound('pesan')`.
  - Pesan error ditulis dalam bahasa Indonesia yang jelas untuk pengguna, karena frontend menampilkannya apa adanya.
- **Asset Timeline wajib diisi.** Setiap aksi yang mengubah data motor harus memanggil `(new AssetActivityModel())->log(...)`. Aksi itu termasuk motor masuk, ubah status, inspeksi, biaya, sparepart, dokumen, booking, dan terjual. Kalau aksinya mengubah status motor, isi argumen `$status`.
- **Uang** disimpan sebagai bilangan bulat rupiah, bukan desimal.
- **Tanggal** disimpan dengan format `YYYY-MM-DD`.
- **Total modal motor** dihitung dari harga beli ditambah jumlah `motor_costs.amount` dan jumlah `sparepart_usage.total`. Gunakan `MotorModel::MODAL_SQL` supaya rumusnya sama di semua tempat.
- **Transaksi database.** Proses yang mengubah lebih dari satu tabel dibungkus `$this->db->transStart()` dan `transComplete()`. Contohnya barang keluar, yang mengurangi stok, mencatat kartu stok, mencatat pemakaian sparepart, dan mengisi timeline.
- **Route** tinggal dibuka komentarnya di `app/Config/Routes.php`. Kalau menambah route baru, ikuti pola yang sama.

### Frontend (Vue 3)

- **Memanggil API** lewat `api.get/post/put/del` dari `@/api`. Token sudah dikirim otomatis.
- **Error dari API** ditangkap dengan `try/catch`, lalu `e.message` ditampilkan ke pengguna.
- **Tombol aksi** disembunyikan kalau user tidak berhak, memakai `can('motor.edit')` dari `@/stores/auth`. Backend tetap mengecek ulang, jadi ini hanya untuk kenyamanan tampilan.
- **Halaman baru**: buat view di `src/views/`, lalu ganti `PlaceholderView` untuk menu terkait di `src/router.js`. Setelah tahapnya selesai, ubah `stage` menu itu di `src/utils/constants.js` menjadi `1` supaya label "T2"/"T3" di sidebar hilang.
- **Komponen yang dipakai lebih dari satu halaman** dibuat di `src/components/`, misalnya gauge Health Score dan timeline.
- **Format rupiah dan tanggal** memakai `rupiah()` dan `tanggal()` dari `@/utils/format`.
- **Tampilan** menggunakan kelas Bootstrap 5 ditambah kelas `nx-*` di `src/assets/nexa.css`. Warna dan bentuk mengikuti prototipe.

### Kalau struktur database berubah

1. Buat migration baru di `app/Database/Migrations`. Jangan mengedit migration lama yang sudah dipakai tim.
2. Perbarui `database/schema.sql`.
3. Kalau data dummy ikut berubah, edit `tools/generate_dummy.py`, lalu jalankan `python tools/generate_dummy.py`.
4. Beri tahu tim di grup WhatsApp, karena semua orang perlu menjalankan `php spark migrate`.

---

## Tahap 1 — Fondasi (SELESAI)

### Yang sudah dibuat

**Database**
- Migration seluruh 15 tabel sesuai ERD: roles, users, api_tokens, motors, inspections, inspection_items, cost_categories, motor_costs, inventory_items, inventory_transactions, sparepart_usage, vehicle_documents, customers, sales, asset_activities.
- Data dummy lengkap untuk semua tabel, jadi Tahap 2–5 tidak perlu membuat data dummy lagi.

**Backend**
- Login berbasis token. Token berlaku 12 jam dan disimpan dalam bentuk hash.
- Pengecekan role di `BaseApiController`.
- Endpoint yang sudah jalan: `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`, `GET /api/meta`, `GET /api/dashboard`.
- `Config/Nexa.php` berisi status lifecycle, hak akses, dan checklist inspeksi.

**Frontend**
- Halaman login dengan tombol isi otomatis untuk akun dummy.
- Layout sidebar yang menu-nya disesuaikan per role.
- Penjaga route: halaman tertutup sebelum login, dan role yang tidak berhak diarahkan ke dashboard.
- Dashboard dasar: ringkasan angka, posisi kendaraan per status, dan aktivitas terbaru.
- Halaman placeholder untuk menu Tahap 2–5.

### Cara memeriksa Tahap 1

| No | Langkah | Hasil yang benar |
| --- | --- | --- |
| 1 | Login `admin` / `admin123` | Masuk ke dashboard. Sidebar berisi 8 menu. |
| 2 | Login `penjualan` / `sales123` | Menu hanya Dashboard, Kendaraan, Penjualan, Laporan. |
| 3 | Login dengan password salah | Muncul "Username/email atau password salah." |
| 4 | Login dengan `admin@nexa.local` | Berhasil (email bisa dipakai sebagai username). |
| 5 | Saat login sebagai `penjualan`, buka `/pengguna` lewat address bar | Diarahkan kembali ke dashboard. |
| 6 | Lihat ringkasan dashboard | Modal tertanam Rp 102.180.000, laba Rp 4.525.000, nilai stok Rp 10.145.000, 4 barang di batas minimum. |
| 7 | Lihat bagian posisi kendaraan | 1 baru masuk, 1 diperiksa, 1 perbaikan, 1 siap, 1 booking, 2 terjual. |
| 8 | Klik Keluar, lalu buka `/dashboard` | Diarahkan ke halaman login. |

### Untuk direview tim

- **Mulyo:** cocokkan migration dan `schema.sql` dengan ERD di proposal. Kalau ada kolom yang perlu ditambah, ikuti aturan di bagian "Kalau struktur database berubah".
- **Akmal:** cek apakah data dummy (biaya, harga, nama sparepart) sudah realistis menurut pengalaman usaha.
- **Firmansyah:** cek tampilan layout dan login, terutama di layar HP.

---

## Tahap 2 — Data kendaraan, dokumen, Asset Timeline

**Tujuan:** pengguna bisa mencatat motor masuk, melihat daftar dan detail motor lengkap dengan riwayatnya, mengubah status, dan mencatat dokumen.

### Backend

File yang dibuat:
- `Controllers/Api/MotorController.php`
- `Controllers/Api/DocumentController.php`
- `Models/VehicleDocumentModel.php`

| Method | Endpoint | Hak akses | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/motors?q=&status=` | motor.view | Daftar motor + `total_modal`, `sale_price` (dari tabel sales kalau ada, kalau tidak dari `target_price`), `profit`. Pencarian `q` mencakup kode, merek, tipe, nomor polisi. Role `sales` hanya melihat status siap, booking, dan terjual. |
| POST | `/api/motors` | motor.edit | Wajib: brand, model, year, plate_number, purchase_date, purchase_price. Kode dibuat otomatis (`NX-008`, dst.). Status awal `masuk`. Catat aktivitas `masuk`. |
| GET | `/api/motors/{id}` | motor.view | Satu motor lengkap: data motor, `summary` (harga beli, biaya per kategori, total sparepart, total modal, harga jual/target, laba), `activities` (urut tanggal naik), `documents`. Tahap 3–5 menambahkan `inspections`, `costs`, `parts`, `sale`. |
| PUT | `/api/motors/{id}` | motor.edit | Edit identitas dan harga. Status tidak diubah di sini. |
| POST | `/api/motors/{id}/status` | motor.status | Body `{status, date, note}`. Hanya boleh ke `masuk`, `inspeksi`, `perbaikan`, `siap`. Status `booking` dan `terjual` hanya lewat penjualan (Tahap 5). Catat aktivitas `status` dengan isian `status`. |
| POST | `/api/motors/{id}/documents` | doc.edit | Body `{doc_type, doc_number, status, expiry_date, note}`. Nilai `status`: Lengkap, Dalam proses, atau Tidak ada. Catat aktivitas `dokumen`. |
| PUT | `/api/documents/{id}` | doc.edit | Edit dokumen. Catat aktivitas `dokumen`. |
| DELETE | `/api/documents/{id}` | doc.edit | Hapus dokumen. |

### Frontend

**`views/MotorListView.vue`**
- Kolom pencarian dan chip filter status beserta jumlahnya.
- Tabel berisi kode, motor, nomor polisi, status, kondisi, total modal, harga, dan laba.
- Klik baris membuka detail motor.

**`views/MotorDetailView.vue`** (route `/kendaraan/:id/:tab?`)
- Header berisi nama motor, plat nomor, dan status.
- Tombol Ubah status dan Edit data.
- Komponen `LifecycleRoad.vue`: jalur 6 status, dengan tanggal saat tiap status tercapai (diambil dari aktivitas yang berisi `status`).
- Kartu identitas kendaraan dan kartu ringkasan modal.
- Tab Asset Timeline (komponen `ActivityTimeline.vue`) dan tab Dokumen.
- Tab Inspeksi, Biaya, Sparepart, dan Penjualan tampil sebagai teks "dikerjakan di Tahap 3/4/5".

**Modal form**
- Form tambah/edit motor.
- Form ubah status.
- Form dokumen.
- Buat dulu satu komponen `BaseModal.vue` yang bisa dipakai ulang di tahap berikutnya.

### Kriteria selesai dan uji

| No | Uji | Hasil yang benar |
| --- | --- | --- |
| 1 | Daftar kendaraan sebagai `admin` | 7 motor tampil. |
| 2 | Daftar kendaraan sebagai `penjualan` | Hanya 4 motor (NX-001 sampai NX-004). |
| 3 | Cari "vario" | Hanya NX-005. |
| 4 | Detail NX-005 Vario | Total modal Rp 12.800.000. Rinciannya: beli Rp 11.000.000, operasional Rp 470.000, perbaikan Rp 850.000, sparepart Rp 480.000. Timeline berisi 13 kejadian. |
| 5 | Tambah motor baru | Mendapat kode NX-008, status Baru masuk, timeline berisi "Motor masuk". |
| 6 | Ubah status NX-007 ke Sedang diperiksa | Jalur status bergerak, timeline bertambah. |
| 7 | Login `pemilik`, buka detail motor | Tombol Edit dan Ubah status tidak ada. API PUT mengembalikan 403. |
| 8 | Tambah dokumen STNK ke NX-007 | Muncul di tab Dokumen dan di timeline. |

---

## Tahap 3 — Inspeksi, Asset Health Score, biaya

**Tujuan:** mekanik mengisi checklist inspeksi dan skornya terhitung otomatis. Staff juga bisa mencatat semua biaya, termasuk yang kecil, dan biaya itu langsung menambah modal motor.

### Rumus Asset Health Score

Rumus ini sudah ada di `Config/Nexa.php` dan sudah dipakai untuk membuat data dummy.

1. **Nilai item:** baik = 1, perlu perbaikan = 0,5, rusak = 0.
2. **Nilai grup:** rata-rata nilai item di grup itu.
3. **Bobot grup:** mesin 35%, kelistrikan 20%, rem & body 30%, dokumen 15%.
4. **Skor akhir:** jumlah dari (bobot grup × nilai grup), dibulatkan, dengan rentang 0–100.
5. **Label skor:** 80 ke atas Sehat (hijau), 60–79 Perlu perhatian (kuning), di bawah 60 Kritis (merah).

Contoh dari data dummy: NX-005 Vario mendapat skor 73. Buat class `Libraries/HealthScore.php` dengan method `calculate(array $items): int` supaya rumus ini bisa diuji sendiri.

### Backend

File yang dibuat:
- `Controllers/Api/InspectionController.php`
- `Controllers/Api/CostController.php`
- `Libraries/HealthScore.php`
- `Models/InspectionModel.php`, `InspectionItemModel.php`, `MotorCostModel.php`, `CostCategoryModel.php`

| Method | Endpoint | Hak akses | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/inspections` | inspect | Riwayat inspeksi semua motor + daftar motor yang menunggu inspeksi (status masuk/inspeksi dan belum pernah diinspeksi). |
| POST | `/api/inspections` | inspect | Lihat rincian proses di bawah tabel. |
| GET | `/api/costs?motor_id=&category_id=` | cost.view | Daftar biaya + total per kategori. |
| POST | `/api/costs` | cost.edit | Body `{motor_id, category_id, item, amount, cost_date, note}`. `amount` harus lebih dari 0. Catat aktivitas `biaya`. |
| DELETE | `/api/costs/{id}` | cost.edit | Hapus biaya. Catat aktivitas "Biaya dihapus" supaya jejaknya tetap ada. |

Proses `POST /api/inspections`:
- **Body:** `{motor_id, inspection_date, inspector, notes, items: {m1:'baik', ...}, to_repair: true}`.
- **Simpan:** header inspeksi dan 18 item checklist.
- **Skor:** hitung skornya, lalu perbarui `motors.health_score`.
- **Status:** kalau motor masih berstatus `masuk`, ubah ke `inspeksi` dulu. Kalau `to_repair` bernilai true dan ada item yang tidak baik, ubah ke `perbaikan`.
- **Timeline:** catat aktivitas `inspeksi` yang berisi daftar temuan.
- Semua langkah dalam satu transaksi database.

Detail motor (`GET /api/motors/{id}`) ditambah dua bagian:
- `inspections`, lengkap dengan item-nya.
- `costs`.

### Frontend

**`views/InspectionListView.vue`**
- Panel "Menunggu inspeksi".
- Tabel riwayat inspeksi.

**`views/InspectionFormView.vue`** (route `/inspeksi/baru/:motorId?`)
- Pilihan motor.
- Checklist per grup, dengan tombol Baik / Perlu perbaikan / Rusak di tiap item.
- Panel samping berisi gauge skor yang berubah langsung saat item dipilih. Hitung skornya di frontend dengan rumus yang sama.

**`views/CostView.vue`**
- Ringkasan total per kategori.
- Filter motor dan kategori.
- Tabel biaya.
- Tombol "Catat biaya".

**Detail motor**
- Isi tab Inspeksi dan tab Biaya.
- Tambahkan kartu Asset Health Score yang berisi gauge dan nilai per grup.

**Komponen**
- `HealthGauge.vue`
- `HealthBadge.vue`

### Kriteria selesai dan uji

| No | Uji | Hasil yang benar |
| --- | --- | --- |
| 1 | Daftar menunggu inspeksi | NX-006 Ninja dan NX-007 Scoopy. |
| 2 | Inspeksi NX-006, semua item Baik | Skor 100, label Sehat. Status tetap Sedang diperiksa. |
| 3 | Inspeksi NX-007: Suara mesin Rusak, Lampu Perlu perbaikan | Skor 89. Status pindah ke Dalam perbaikan. Timeline mencatat temuan. |
| 4 | Detail NX-005 | Health Score 73. Nilai per grup: mesin 63, kelistrikan 90, rem & body 60, dokumen 100. |
| 5 | Catat biaya Rp 50.000 ke NX-007 | Total modal NX-007 naik dari Rp 14.300.000 menjadi Rp 14.350.000. |
| 6 | Catat biaya dengan nominal 0 | Ditolak dengan pesan yang jelas. |
| 7 | Total semua biaya di halaman Biaya | Rp 10.180.000 (data dummy awal). |
| 8 | Login `pemilik` | Bisa melihat halaman Biaya, tapi tombol Catat biaya tidak ada. |

---

## Tahap 4 — Inventaris dan sparepart

**Tujuan:** stok sparepart tercatat rapi, ada peringatan stok minimum, dan setiap sparepart yang dipakai untuk motor otomatis masuk ke modal motor tersebut.

### Aturan bisnis

- **Indikator stok:**
  - Merah kalau `stock <= min_stock`.
  - Kuning kalau `stock <= ceil(min_stock × 1,5)`.
  - Selain itu aman.
- **Barang masuk:**
  - Menambah stok.
  - Mencatat transaksi `in` beserta supplier.
  - Kalau harga diisi, harga satuan barang diperbarui.
- **Barang keluar:**
  - Tidak boleh melebihi stok yang ada.
  - Mengurangi stok.
  - Mencatat transaksi `out` dengan harga satuan saat itu.
  - Kalau dipakai untuk motor, juga mencatat `sparepart_usage` (qty × harga) dan aktivitas `sparepart` di timeline motor.
  - Semua langkah dalam satu transaksi database.
- **Satuan barang:** pcs, set, botol, liter, meter, atau pasang. Wawancara di Lampiran B menunjukkan ada barang yang dibeli per set.

### Backend

File yang dibuat:
- `Controllers/Api/InventoryController.php`
- `Models/InventoryItemModel.php`, `InventoryTransactionModel.php`, `SparepartUsageModel.php`

| Method | Endpoint | Hak akses | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/inventory?q=` | inv.view | Daftar barang + `level` (bad/warn/good) + nilai stok. |
| POST | `/api/inventory` | inv.edit | Barang baru. Kode harus unik. Kalau stok awal diisi, catat sebagai transaksi `in` "Stok awal". |
| PUT | `/api/inventory/{id}` | inv.edit | Edit nama, satuan, harga, minimum, dan lokasi. Stok tidak diubah di sini. |
| GET | `/api/inventory/transactions` | inv.view | Kartu stok, berisi nama barang dan motor terkait. |
| POST | `/api/inventory/in` | inv.edit | Body `{item_id, qty, unit_price, supplier, trx_date}`. |
| POST | `/api/inventory/out` | inv.edit | Body `{item_id, qty, motor_id (boleh kosong), trx_date, note}`. |

Detail motor ditambah bagian `parts` (sparepart yang dipakai).

### Frontend

**`views/InventoryView.vue`**
- Ringkasan: jenis barang, nilai inventaris, jumlah barang merah, jumlah barang kuning.
- Tab Stok dan tab Kartu stok.
- Tombol Barang masuk, Barang keluar, dan Barang baru.
- Garis warna di kiri baris untuk barang yang stoknya menipis.

**Detail motor**
- Isi tab Sparepart.
- Tombol "Pakai sparepart" yang membuka form barang keluar dengan motor sudah terpilih.

### Kriteria selesai dan uji

| No | Uji | Hasil yang benar |
| --- | --- | --- |
| 1 | Lihat stok | 13 barang. Merah: Shockbreaker, Oli samping, Spion, Knalpot. Kuning: Velg, Kampas rem, Ban luar. |
| 2 | Nilai inventaris | Rp 10.145.000. |
| 3 | Barang keluar Headlamp 999 pcs | Ditolak dengan pesan "Stok Headlamp LED hanya 4 pcs." |
| 4 | Pakai 1 Busi untuk NX-007 | Stok busi 22 → 21. Modal NX-007 bertambah Rp 25.000. Timeline NX-007 mencatat pemakaian. Kartu stok mencatat transaksi keluar. |
| 5 | Barang masuk 3 Spion dari supplier | Stok 2 → 5. Indikator berubah dari merah menjadi kuning (minimum 3, batas kuning 5). |
| 6 | Login `pemilik` | Bisa melihat inventaris, tapi tidak ada tombol aksi. |

---

## Tahap 5 — Penjualan, laporan, pengguna, dashboard lengkap

**Tujuan:** siklus motor lengkap sampai terjual, laporan bisa diunduh, admin bisa mengelola pengguna, lalu UAT bersama pemilik usaha.

### Persiapan

Jalankan di folder `backend`:

```bash
composer require phpoffice/phpspreadsheet dompdf/dompdf
```

### Aturan bisnis penjualan

- **Syarat:** hanya motor berstatus `siap` yang bisa dibooking atau dijual.
- **Booking:**
  - Membuat data `sales` dengan status `Booking`.
  - DP tidak boleh melebihi harga.
  - Status motor menjadi `booking`.
- **Selesaikan penjualan:**
  - Mengisi `sale_date` dan harga final.
  - Status pembayaran menjadi `Lunas` atau `Belum lunas`.
  - Status motor menjadi `terjual`.
- **Penjualan langsung:** booking dan penyelesaian dilakukan sekaligus.
- **Batalkan booking:**
  - Status pembayaran menjadi `Batal`.
  - Status motor kembali menjadi `siap`.
- **Laba:** harga jual dikurangi total modal.
- Semua aksi di atas dicatat di timeline motor.

### Backend

File yang dibuat:
- `CustomerController`, `SaleController`, `ReportController`, `UserController`, `NotificationController`
- `Libraries/ReportBuilder.php`
- Model untuk customers dan sales
- `Views/reports/pdf.php` (template PDF)

| Method | Endpoint | Hak akses | Keterangan |
| --- | --- | --- | --- |
| GET/POST | `/api/customers` | sale.view / sale.edit | Daftar dan tambah customer. |
| GET | `/api/sales` | sale.view | Semua transaksi + laba. |
| POST | `/api/sales` | sale.edit | Body `{motor_id, customer_id atau customer baru {name, phone, address}, price, dp, date, mode: booking/lunas/belum}`. |
| POST | `/api/sales/{id}/complete` | sale.edit | Body `{price, sale_date, payment_status}`. |
| POST | `/api/sales/{id}/paid` | sale.edit | Tandai lunas. |
| POST | `/api/sales/{id}/cancel` | sale.edit | Batalkan booking. |
| GET | `/api/reports/{jenis}?month=&format=json/xlsx/pdf` | report.view | Lihat daftar jenis laporan di bawah tabel. |
| GET/POST/PUT | `/api/users`, `/api/users/{id}`, `/api/users/{id}/toggle` | user.manage | Lihat aturan pengguna di bawah tabel. |
| GET | `/api/notifications` | login | Lihat isi notifikasi di bawah tabel. |

Jenis laporan untuk `/api/reports/{jenis}`:
- `motor`: biaya & keuntungan per motor.
- `stok`: stok gudang & nilai inventaris.
- `pemakaian`: pemakaian sparepart bulanan.
- `penjualan`: rekap penjualan.

Format `json` dipakai untuk pratinjau. Format `xlsx` dibuat dengan PhpSpreadsheet dan `pdf` dengan Dompdf, keduanya dikirim sebagai file unduhan.

Aturan manajemen pengguna:
- Username harus unik.
- Password di-hash dengan `password_hash`.
- Admin tidak bisa menonaktifkan akunnya sendiri.

Isi notifikasi:
- Stok di batas minimum.
- Motor menunggu inspeksi.
- Health Score kritis.
- Booking menunggu pelunasan.
- Pembayaran belum lunas.

Tambahan di dashboard (`GET /api/dashboard`):
- `chart`: modal vs harga per motor.
- `cost_composition`: total per kategori + sparepart.
- `notifications`.

### Frontend

**`SalesView.vue`**
- Tab Transaksi, Siap dijual, dan Customer.
- Form booking/penjualan, dengan pilihan customer lama atau customer baru.

**`ReportView.vue`**
- Pilihan jenis laporan.
- Tabel pratinjau.
- Tombol Unduh Excel dan Unduh PDF, memakai `download()` dari `@/api`.

**`UserView.vue`**
- Tabel pengguna.
- Form tambah/edit.
- Tabel hak akses per role.

**Detail motor**
- Isi tab Penjualan.

**Dashboard**
- Grafik modal vs harga jual (`ModalChart.vue`, SVG, tanpa library tambahan).
- Komposisi biaya.
- Panel "Perlu ditindaklanjuti".
- Ikon lonceng notifikasi di topbar.

### Kriteria selesai dan uji

| No | Uji | Hasil yang benar |
| --- | --- | --- |
| 1 | Daftar transaksi | 3 transaksi: 2 lunas, 1 booking (RX-King, DP Rp 2.000.000). |
| 2 | Laba NX-003 Beat | Rp 2.985.000 (jual Rp 13.800.000 − modal Rp 10.815.000). |
| 3 | Jual langsung NX-004 NMAX Rp 25.500.000 lunas ke customer baru | Status Terjual. Customer baru tersimpan. Laba Rp 1.860.000. |
| 4 | Booking dengan DP lebih besar dari harga | Ditolak. |
| 5 | Batalkan booking RX-King | Status kembali Siap dijual. Timeline mencatat pembatalan. |
| 6 | Unduh laporan biaya & keuntungan (Excel) | File .xlsx terbuka di Excel dan baris total benar. |
| 7 | Unduh laporan stok (PDF) | File .pdf berisi 13 barang. |
| 8 | Laporan pemakaian bulan September 2026 | 7 unit, total Rp 715.000. |
| 9 | Admin menonaktifkan akun `mekanik` | Akun itu tidak bisa login lagi. |
| 10 | Grafik dashboard | 7 motor, batang modal dan harga. |

### Penutupan proyek

- **Kemal:** susun test case dan rekap hasil uji semua tahap, user manual dengan tangkapan layar per role, dan log kegiatan.
- **Akmal:** pimpin UAT bersama pemilik usaha, catat masukan, dan buat berita acara UAT.
- **Alifya:** finalisasi laporan dan demo.

---

## Peta data dummy (untuk pengujian)

| Motor | Status | Gunanya untuk uji |
| --- | --- | --- |
| NX-001 Suzuki Satria F150 | Terjual, lunas | Laba Rp 1.540.000. Contoh pengambilan luar kota (travel). |
| NX-002 Yamaha RX-King | Booking (DP Rp 2.000.000) | Pelunasan dan pembatalan booking. BPKB masih "Dalam proses". |
| NX-003 Honda Beat Street | Terjual, lunas | Contoh biaya kecil lengkap: bensin, uang makan, pengiriman. |
| NX-004 Yamaha NMAX 155 | Siap dijual | Uji booking atau penjualan langsung. |
| NX-005 Honda Vario 150 | Dalam perbaikan, skor 73 | Uji modal, sparepart, dan Health Score "Perlu perhatian". |
| NX-006 Kawasaki Ninja 250 FI | Sedang diperiksa, belum ada inspeksi | Uji form inspeksi pertama. |
| NX-007 Honda Scoopy | Baru masuk | Uji ubah status, biaya, dan dokumen dari awal. |

Biaya dan harga sparepart mengikuti hasil wawancara (Lampiran B):
- jasa pengambilan Rp 300.000,
- uang makan Rp 70.000,
- bensin Rp 100.000,
- travel luar kota Rp 300.000,
- aksesori Rp 2.500.000,
- STNK Rp 1.000.000,
- headlamp Rp 500.000,
- shockbreaker Rp 400.000,
- velg Rp 1.300.000 per set.

---

## Catatan serah terima

Isi tabel ini setiap kali sebuah tahap selesai di-merge. Tuliskan juga hal yang belum beres, supaya orang di tahap berikutnya tahu.

| Tahap | Tanggal selesai | Dikerjakan oleh | Uji lolos | Catatan untuk tahap berikutnya |
| --- | --- | --- | --- | --- |
| 1 | 20 Okt 2026 | (draf awal, perlu review tim) | Lihat tabel uji Tahap 1 | Kode ditulis tanpa dijalankan di lingkungan PHP. Jalankan `setup.bat` dan uji 1–8 dulu, lalu catat perbaikannya di sini. |
| 2 | | | | |
| 3 | | | | |
| 4 | | | | |
| 5 | | | | |
