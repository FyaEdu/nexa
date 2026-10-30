// Konstanta bisnis NEXA. Harus sama dengan backend/application/config/nexa.php.

export const ROLES = {
  admin: 'Administrator',
  owner: 'Pemilik Usaha',
  ops: 'Staff Operasional',
  sales: 'Staff Penjualan'
}
export const ALL_ROLES = Object.keys(ROLES)

export const STATUSES = [
  { key: 'masuk', label: 'Baru masuk' },
  { key: 'inspeksi', label: 'Sedang diperiksa' },
  { key: 'perbaikan', label: 'Dalam perbaikan' },
  { key: 'siap', label: 'Siap dijual' },
  { key: 'booking', label: 'Booking' },
  { key: 'terjual', label: 'Terjual' }
]
export const statusLabel = (key) => STATUSES.find((s) => s.key === key)?.label ?? key

export const STAGES = {
  1: 'Fondasi: database, login, layout',
  2: 'Data kendaraan, dokumen, dan Asset Timeline',
  3: 'Inspeksi, Asset Health Score, dan biaya',
  4: 'Inventaris dan sparepart',
  5: 'Penjualan, laporan, pengguna, dan dashboard lengkap'
}

// Tahap terakhir yang sudah selesai. Menu dengan stage di atas angka ini masih placeholder.
export const DONE_STAGE = 4

// Menu utama. "stage" = tahap pengerjaan (lihat PANDUAN-TAHAP.md).
// "todo" ditampilkan di halaman placeholder sampai tahapnya selesai.
export const MENU = [
  { key: 'dashboard', label: 'Dashboard', path: '/dashboard', icon: 'bi-grid', roles: ALL_ROLES, stage: 1 },
  {
    key: 'kendaraan', label: 'Kendaraan', path: '/kendaraan', icon: 'bi-bicycle', roles: ALL_ROLES, stage: 2,
    todo: ['Daftar motor dengan pencarian dan filter status', 'Tambah dan edit data motor', 'Detail motor: identitas, ringkasan modal, jalur status', 'Asset Timeline dan dokumen kendaraan']
  },
  {
    key: 'inspeksi', label: 'Inspeksi', path: '/inspeksi', icon: 'bi-clipboard-check', roles: ['admin', 'ops'], stage: 3,
    todo: ['Checklist inspeksi mesin, kelistrikan, rem & body, dokumen', 'Hitung Asset Health Score otomatis', 'Riwayat inspeksi per motor']
  },
  {
    key: 'biaya', label: 'Biaya', path: '/biaya', icon: 'bi-wallet2', roles: ['admin', 'owner', 'ops'], stage: 3,
    todo: ['Catat biaya per motor per kategori', 'Filter biaya per motor dan kategori', 'Biaya otomatis masuk ke total modal']
  },
  {
    key: 'inventaris', label: 'Inventaris', path: '/inventaris', icon: 'bi-box-seam', roles: ['admin', 'owner', 'ops'], stage: 4,
    todo: ['Stok sparepart dengan indikator merah/kuning', 'Barang masuk dari supplier dan barang keluar ke motor', 'Kartu stok (riwayat masuk/keluar)']
  },
  {
    key: 'penjualan', label: 'Penjualan', path: '/penjualan', icon: 'bi-tag', roles: ['admin', 'owner', 'sales'], stage: 5,
    todo: ['Customer', 'Booking dengan DP, pelunasan, pembatalan', 'Penjualan langsung']
  },
  {
    key: 'laporan', label: 'Laporan', path: '/laporan', icon: 'bi-bar-chart', roles: ['admin', 'owner', 'sales'], stage: 5,
    todo: ['Biaya & keuntungan per motor', 'Stok gudang & nilai inventaris', 'Pemakaian sparepart bulanan', 'Unduh Excel (PhpSpreadsheet) dan PDF (Dompdf)']
  },
  {
    key: 'pengguna', label: 'Pengguna', path: '/pengguna', icon: 'bi-people', roles: ['admin'], stage: 5,
    todo: ['Tambah, edit, nonaktifkan pengguna', 'Atur role / hak akses']
  }
]

// Checklist inspeksi. Harus sama dengan $config['checklist'] di backend/application/config/nexa.php
export const CHECKLIST = [
  { key: 'mesin', label: 'Kondisi mesin', weight: 35, items: [['m1', 'Suara mesin'], ['m2', 'Kebocoran oli'], ['m3', 'Sistem pengapian'], ['m4', 'Performa tarikan']] },
  { key: 'listrik', label: 'Sistem kelistrikan', weight: 20, items: [['l1', 'Aki'], ['l2', 'Lampu depan/belakang'], ['l3', 'Lampu sein'], ['l4', 'Klakson'], ['l5', 'Starter']] },
  { key: 'rem', label: 'Pengereman & body', weight: 30, items: [['r1', 'Ketebalan kampas rem'], ['r2', 'Fungsi rem depan/belakang'], ['r3', 'Kondisi fisik body'], ['r4', 'Ketersediaan spion'], ['r5', 'Keausan ban']] },
  { key: 'dok', label: 'Kelengkapan dokumen', weight: 15, items: [['d1', 'Nomor rangka sesuai'], ['d2', 'Nomor mesin sesuai'], ['d3', 'STNK'], ['d4', 'BPKB']], labels: { baik: 'Sesuai', perlu: 'Bermasalah', rusak: 'Tidak ada' } }
]
export const RESULT_LABELS = { baik: 'Baik', perlu: 'Perlu perbaikan', rusak: 'Rusak' }

export const MANUAL_STATUSES = ['masuk', 'inspeksi', 'perbaikan', 'siap']
export const DOC_TYPES = ['STNK', 'BPKB', 'Faktur', 'Kuitansi pembelian', 'KTP pemilik lama', 'Lainnya']
export const DOC_STATUSES = ['Lengkap', 'Dalam proses', 'Tidak ada']
export const UNITS = ['pcs', 'set', 'botol', 'liter', 'meter', 'pasang']

// Contoh rincian biaya per kategori (dari wawancara, Lampiran B) untuk saran isian
export const COST_SUGGESTIONS = {
  Operasional: ['Jasa pengambilan kendaraan', 'Uang makan pengambilan', 'Bensin pengambilan', 'Travel pengambilan luar kota', 'Bahan bakar pengiriman Jabodetabek', 'Uang makan pengiriman', 'Travel pengiriman luar kota'],
  Perbaikan: ['Aksesori', 'Sparepart', 'Bahan pendukung', 'Jasa servis bengkel', 'Turun mesin', 'Cat dan perbaikan body'],
  'Surat-surat': ['Pajak tahunan', 'STNK', 'BPKB', 'Balik nama', 'Cek fisik'],
  Penyimpanan: ['Sewa gudang', 'Sewa tempat parkir'],
  Lainnya: ['Biaya lain-lain']
}

// Ikon Bootstrap Icons per jenis aktivitas timeline
export const ACTIVITY_ICONS = {
  masuk: 'bi-box-arrow-in-down', status: 'bi-flag', edit: 'bi-pencil', inspeksi: 'bi-clipboard-check',
  biaya: 'bi-wallet2', sparepart: 'bi-wrench', dokumen: 'bi-file-text', booking: 'bi-tag',
  terjual: 'bi-check-lg', batal: 'bi-arrow-counterclockwise'
}
