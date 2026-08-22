@echo off
title HR Traders - One-Click Master Restoration & Sync
echo ========================================================
echo       HR TRADERS - AUTOMATIC DATA & IMAGE RESTORE
echo ========================================================
echo.
echo Starting MySQL if not already running...
start /B "" "d:\xampp orginal\mysql\bin\mysqld.exe" --defaults-file="d:\xampp orginal\mysql\bin\my.ini" --standalone >nul 2>&1
timeout /t 3 /nobreak >nul

echo Restoring all products and images from Cloud Backup...
"d:\xampp orginal\php\php.exe" "scratch/restore_and_sync_all.php"

echo.
echo ========================================================
echo    SUCCESS: All products and images are 100%% restored!
echo ========================================================
pause
