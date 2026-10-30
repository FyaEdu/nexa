@echo off
cd /d "%~dp0"
where docker >nul 2>nul || (echo [!] Docker tidak ditemukan. Pasang Docker Desktop dari https://www.docker.com/products/docker-desktop & pause & exit /b 1)
docker info >nul 2>nul || (echo [!] Docker Desktop belum berjalan. Buka Docker Desktop dulu, tunggu sampai siap, lalu jalankan lagi. & pause & exit /b 1)

echo Menjalankan NEXA di Docker...
docker compose up -d --build || (pause & exit /b 1)

echo Menunggu backend siap. Pertama kali bisa 3-10 menit karena mengunduh PHP 7.3, CodeIgniter 3, dan paket npm.
echo Lihat prosesnya di jendela lain dengan: docker compose logs -f
:wait
timeout /t 5 >nul
curl -s -o nul http://localhost:5173/ || goto wait
start "" http://localhost:5173
echo NEXA berjalan di http://localhost:5173  ^(hentikan dengan docker-stop.bat^)
pause
