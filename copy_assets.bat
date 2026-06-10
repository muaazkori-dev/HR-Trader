@echo off
title HR Traders - Asset Copy Utility
echo ===================================================
echo     HR TRADERS PREMIUM ASSETS DEPLOYMENT SCRIPT
echo ===================================================
echo.

echo Step 1: Copying new brand logo...
copy "C:\Users\Administrator\.gemini\antigravity\brain\1419d0d6-16b6-426a-9bf0-925d8b5f8b89\hr_traders_logo_1781111060749.png" "assets\images\logo.png" /y

echo.
echo Step 2: Copying new high-res favicon...
copy "C:\Users\Administrator\.gemini\antigravity\brain\1419d0d6-16b6-426a-9bf0-925d8b5f8b89\hr_traders_favicon_1781111078204.png" "assets\images\favicon.png" /y

echo.
echo Step 3: Copying redesigned category icons (PNGs)...
copy "C:\Users\Administrator\.gemini\antigravity\brain\1419d0d6-16b6-426a-9bf0-925d8b5f8b89\anaj_category_1781111095610.png" "assets\images\categories\anaj.png" /y
copy "C:\Users\Administrator\.gemini\antigravity\brain\1419d0d6-16b6-426a-9bf0-925d8b5f8b89\ice_cream_category_1781111112679.png" "assets\images\categories\ice_cream.png" /y
copy "C:\Users\Administrator\.gemini\antigravity\brain\1419d0d6-16b6-426a-9bf0-925d8b5f8b89\beverages_category_1781111130213.png" "assets\images\categories\cold_drinks.png" /y
copy "C:\Users\Administrator\.gemini\antigravity\brain\1419d0d6-16b6-426a-9bf0-925d8b5f8b89\milk_category_1781111150329.png" "assets\images\categories\milk.png" /y
copy "C:\Users\Administrator\.gemini\antigravity\brain\1419d0d6-16b6-426a-9bf0-925d8b5f8b89\cosmetics_category_1781111169166.png" "assets\images\categories\cosmetics.png" /y

echo.
echo ===================================================
echo [SUCCESS] All assets copied successfully!
echo ===================================================
echo.
pause
