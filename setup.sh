#!/usr/bin/env bash
# Setup NEXA untuk macOS / Linux
set -e
cd "$(dirname "$0")"
need() { command -v "$1" >/dev/null 2>&1 || { echo "[!] $1 tidak ditemukan. $2"; exit 1; }; }
need php "Pasang PHP 8.1+."
need composer "Unduh di https://getcomposer.org"
need npm "Pasang Node.js LTS dari https://nodejs.org"
php -m | grep -qix sqlite3 || { echo "[!] Ekstensi PHP sqlite3 belum aktif."; exit 1; }
php -m | grep -qix intl || { echo "[!] Ekstensi PHP intl belum aktif."; exit 1; }

if [ ! -d backend/vendor/codeigniter4 ]; then
  echo "[1/4] Mengunduh CodeIgniter 4..."
  rm -rf _ci4
  composer create-project codeigniter4/appstarter _ci4 --no-interaction
  # Salin file framework yang belum ada, file NEXA tidak ditimpa
  cp -Rn _ci4/. backend/ 2>/dev/null || true
  rm -rf _ci4
else
  echo "[1/4] CodeIgniter 4 sudah terpasang, dilewati."
fi
echo "[2/4] Membuat tabel database..."; (cd backend && php spark migrate)
echo "[3/4] Mengisi data dummy...";     (cd backend && php spark db:seed DatabaseSeeder)
echo "[4/4] npm install...";            (cd frontend && npm install)
echo "Setup selesai. Jalankan ./start.sh"
