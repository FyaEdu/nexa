#!/bin/sh
# Dijalankan setiap container backend start.
set -e
cd /app

# 1. Pasang CodeIgniter 3 (hanya saat pertama kali, folder system/ belum ada).
#    File NEXA di folder backend/application tidak ditimpa.
if [ ! -f system/core/CodeIgniter.php ] || [ ! -f index.php ]; then
  echo "[NEXA] Memasang CodeIgniter 3.1.13. Pertama kali butuh internet dan beberapa menit..."
  rm -rf /tmp/_ci3
  composer create-project codeigniter/framework /tmp/_ci3 3.1.13 --no-dev --no-interaction --no-progress
  cp -Rn /tmp/_ci3/. /app/ || true
  rm -rf /tmp/_ci3
fi

# 2. Paket composer tambahan (mis. PhpSpreadsheet/Dompdf di Tahap 5)
if [ -f composer.json ]; then
  composer install --no-dev --no-interaction --no-progress
fi

# 3. Database: buat/upgrade tabel, isi data dummy kalau database baru
mkdir -p writable
NEW_DB=0
[ -f writable/nexa.db ] || NEW_DB=1
php index.php tools migrate
if [ "$NEW_DB" = "1" ] || [ "$NEXA_RESEED" = "1" ]; then
  php index.php tools seed
fi

echo "[NEXA] Backend siap di http://localhost:8080 . Buka aplikasi di http://localhost:5173"
exec php -S 0.0.0.0:8080 server.php
