@echo off
setlocal
cd /d "%~dp0"
title NEXA - Mulai

rem Pastikan alat bawaan Windows (powershell, findstr, robocopy) selalu ketemu
set "PATH=%SystemRoot%\System32;%SystemRoot%;%SystemRoot%\System32\WindowsPowerShell\v1.0;%PATH%"
echo ==============================================
echo   NEXA - satu klik jalan
echo   Pertama kali butuh internet dan beberapa menit.
echo ==============================================

rem --- 1. Siapkan PHP, Composer, Node (portable, di folder tools\runtime) ---
if exist "tools\runtime\php\php.ini.ok" if exist "tools\runtime\php\php.exe" if exist "tools\runtime\node\node.exe" if exist "tools\runtime\composer.phar" goto runtime_ok
echo.
echo [Langkah 1] Mengunduh PHP, Composer, dan Node.js...
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -ExecutionPolicy Bypass -File "tools\install-runtime.ps1" || goto fail
:runtime_ok

rem Pakai alat portable hanya selama jendela ini (PATH Windows tidak diubah)
set "PATH=%~dp0tools\runtime\php;%~dp0tools\runtime;%~dp0tools\runtime\node;%PATH%"

rem --- 2. Setup project (hanya sekali) ---
if exist ".setup-selesai" goto setup_ok
echo.
echo [Langkah 2] Setup project (download CodeIgniter, buat database, npm install)...
call setup.bat || goto fail
echo ok> ".setup-selesai"
:setup_ok

rem --- 3. Jalankan ---
echo.
echo [Langkah 3] Menjalankan NEXA...
call start.bat
echo.
echo NEXA berjalan di http://localhost:5173
echo Login: admin / admin123
echo Untuk menghentikan, tutup dua jendela hitam (backend dan frontend).
pause
exit /b 0

:fail
echo.
echo Ada masalah. Foto/screenshot pesan di atas lalu kirim ke yang bikin.
pause
exit /b 1
