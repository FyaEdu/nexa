@echo off
cd /d "%~dp0"
echo Database akan dikosongkan dan diisi ulang dengan data dummy awal.
pause
docker compose exec backend php index.php tools reset
pause
