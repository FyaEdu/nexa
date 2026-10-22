// Konstanta bisnis NEXA. Harus sama dengan backend/app/Config/Nexa.php.

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
