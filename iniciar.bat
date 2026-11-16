@echo off
cd /d "%~dp0"
echo Iniciando SIGEBI en http://localhost:8000  (cierre esta ventana para detenerlo)
start "" http://localhost:8000
php -S localhost:8000 -t public
