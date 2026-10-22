@echo off
setlocal
cd /d "%~dp0"
set "PATH=%SystemRoot%\System32;%SystemRoot%;%PATH%"
if exist "%~dp0tools\runtime\php\php.exe" set "PATH=%~dp0tools\runtime\php;%~dp0tools\runtime;%~dp0tools\runtime\node;%PATH%"
echo ==============================================
echo  Setup NEXA - CodeIgniter 4 + Vue 3 + SQLite
echo ==============================================

where php >nul 2>nul || (echo [!] PHP tidak ditemukan. Pasang PHP 8.1+ atau XAMPP lalu tambahkan folder php ke PATH. & goto fail)
where composer >nul 2>nul || (echo [!] Composer tidak ditemukan. Unduh di https://getcomposer.org & goto fail)
where npm >nul 2>nul || (echo [!] Node.js tidak ditemukan. Unduh versi LTS di https://nodejs.org & goto fail)
php -r "exit(extension_loaded('sqlite3')?0:1);" || (echo [!] Ekstensi PHP sqlite3 belum aktif. Ini PHP yang terpakai: & where php & php --ini & goto fail)
php -r "exit(extension_loaded('intl')?0:1);" || (echo [!] Ekstensi PHP intl belum aktif. Ini PHP yang terpakai: & where php & php --ini & goto fail)

if exist "backend\vendor\codeigniter4" goto ci_ready
echo [1/4] Mengunduh CodeIgniter 4 lewat Composer...
if exist "_ci4" rmdir /s /q "_ci4"
call composer create-project codeigniter4/appstarter _ci4 --no-interaction || goto fail
rem Salin file framework yang belum ada. File NEXA di backend tidak ditimpa.
robocopy "_ci4" "backend" /E /XC /XN /XO /NFL /NDL /NJH /NJS /NP >nul
rmdir /s /q "_ci4"
goto ci_done
:ci_ready
echo [1/4] CodeIgniter 4 sudah terpasang, dilewati.
:ci_done

cd backend
echo [2/4] Membuat tabel database SQLite...
php spark migrate || goto fail
echo [3/4] Mengisi data dummy...
php spark db:seed DatabaseSeeder || goto fail
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
