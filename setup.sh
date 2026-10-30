#!/usr/bin/env bash
# Setup NEXA untuk macOS / Linux (CodeIgniter 3, disarankan PHP 7.3)
set -e
cd "$(dirname "$0")"
need() { command -v "$1" >/dev/null 2>&1 || { echo "[!] $1 tidak ditemukan. $2"; exit 1; }; }
need php "Pasang PHP 7.3 atau pakai ./docker-start.sh"
need composer "Unduh di https://getcomposer.org"
need npm "Pasang Node.js LTS dari https://nodejs.org"
php -r "exit(version_compare(PHP_VERSION,'7.3.0','>=') && version_compare(PHP_VERSION,'7.5.0','<') ? 0 : 1);" \
  || echo "[peringatan] PHP $(php -r 'echo PHP_VERSION;') bukan 7.3/7.4. CodeIgniter 3 bisa menampilkan peringatan di PHP 8."
php -m | grep -qix sqlite3 || { echo "[!] Ekstensi PHP sqlite3 belum aktif."; exit 1; }

if [ ! -f backend/system/core/CodeIgniter.php ]; then
  echo "[1/4] Mengunduh CodeIgniter 3.1.13..."
  rm -rf _ci3
  composer create-project codeigniter/framework _ci3 3.1.13 --no-dev --no-interaction
  cp -Rn _ci3/. backend/ 2>/dev/null || true
  rm -rf _ci3
else
  echo "[1/4] CodeIgniter 3 sudah terpasang, dilewati."
fi
echo "[2/4] Membuat tabel database..."; (cd backend && php index.php tools migrate)
echo "[3/4] Mengisi data dummy...";     (cd backend && php index.php tools seed)
echo "[4/4] npm install...";            (cd frontend && npm install)
echo "Setup selesai. Jalankan ./start.sh"
