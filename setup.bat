@echo off
setlocal
cd /d "%~dp0"
echo ==============================================
echo  Setup NEXA - CodeIgniter 3 + Vue 3 + SQLite
echo ==============================================

where php >nul 2>nul || (echo [!] PHP tidak ditemukan. Pasang XAMPP dengan PHP 7.3 lalu tambahkan folder php ke PATH. Atau pakai docker-start.bat. & goto fail)
where composer >nul 2>nul || (echo [!] Composer tidak ditemukan. Unduh di https://getcomposer.org & goto fail)
where npm >nul 2>nul || (echo [!] Node.js tidak ditemukan. Unduh versi LTS di https://nodejs.org & goto fail)
php -r "exit(version_compare(PHP_VERSION, '7.3.0', '>=') && version_compare(PHP_VERSION, '7.5.0', '<') ? 0 : 1);" || echo [peringatan] Versi PHP Anda bukan 7.3/7.4. CodeIgniter 3 bisa menampilkan peringatan di PHP 8. Disarankan PHP 7.3.
php -m | findstr /i /x "sqlite3" >nul || (echo [!] Ekstensi PHP sqlite3 belum aktif. Buka php.ini, hapus titik koma di depan extension=sqlite3 & goto fail)

if exist "backend\system\core\CodeIgniter.php" goto ci_ready
echo [1/4] Mengunduh CodeIgniter 3.1.13 lewat Composer...
if exist "_ci3" rmdir /s /q "_ci3"
call composer create-project codeigniter/framework _ci3 3.1.13 --no-dev --no-interaction || goto fail
rem Salin file framework yang belum ada. File NEXA di backend tidak ditimpa.
robocopy "_ci3" "backend" /E /XC /XN /XO /NFL /NDL /NJH /NJS /NP >nul
rmdir /s /q "_ci3"
goto ci_done
:ci_ready
echo [1/4] CodeIgniter 3 sudah terpasang, dilewati.
:ci_done

cd backend
echo [2/4] Membuat tabel database SQLite...
php index.php tools migrate || goto fail
echo [3/4] Mengisi data dummy...
php index.php tools seed || goto fail
cd ..

echo [4/4] Memasang paket frontend (npm install)...
cd frontend
call npm install || goto fail
cd ..

echo.
echo Setup selesai. Jalankan start.bat untuk membuka aplikasi.
pause
exit /b 0

:fail
echo.
echo Setup berhenti karena ada masalah. Baca pesan di atas, perbaiki, lalu jalankan setup.bat lagi.
pause
exit /b 1
