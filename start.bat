@echo off
cd /d "%~dp0"
start "NEXA backend (port 8080)" cmd /k "cd /d %~dp0backend && php -S localhost:8080 server.php"
start "NEXA frontend (port 5173)" cmd /k "cd /d %~dp0frontend && npm run dev"
echo Menunggu server siap...
timeout /t 5 >nul
start "" http://localhost:5173
