@echo off
cd /d "%~dp0"
set "PATH=%SystemRoot%\System32;%SystemRoot%;%SystemRoot%\System32\WindowsPowerShell\v1.0;%PATH%"
if exist "%~dp0tools\runtime\php\php.exe" set "PATH=%~dp0tools\runtime\php;%~dp0tools\runtime;%~dp0tools\runtime\node;%PATH%"

where php >nul 2>nul || (echo [!] PHP tidak ditemukan. Jalankan MULAI-NEXA.bat dulu. & pause & exit /b 1)
where npm >nul 2>nul || (echo [!] Node.js tidak ditemukan. Jalankan MULAI-NEXA.bat dulu. & pause & exit /b 1)

start "NEXA backend (port 8080)" cmd /k "cd /d %~dp0backend && php spark serve"
start "NEXA frontend (port 5173)" cmd /k "cd /d %~dp0frontend && npm run dev"

echo Menunggu server siap (maks 60 detik)...
set /a n=0
:tunggu
set /a n+=1
"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -Command "try{$c=New-Object Net.Sockets.TcpClient;$c.Connect('127.0.0.1',5173);exit 0}catch{exit 1}" >nul 2>nul
if not errorlevel 1 goto siap
if %n% geq 30 goto siap
timeout /t 2 >nul
goto tunggu
:siap
start "" http://localhost:5173
