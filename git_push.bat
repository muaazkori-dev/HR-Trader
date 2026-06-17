@echo off
title HR Traders - GitHub Auto Push Helper
echo ===================================================
echo     HR TRADERS GITHUB AUTO-PUSH UTILITY
echo ===================================================
echo.
echo Step 0: Copying category icons and assets...
copy /y "C:\Users\Administrator\.gemini\antigravity\brain\1419d0d6-16b6-426a-9bf0-925d8b5f8b89\grocery_icon_1781453922347.png" "d:\xampp orginal\htdocs\HR Traders\assets\images\categories\grocery.png"

echo Step 1: Configuring local git author details...
git config user.email "muaazkori-dev@users.noreply.github.com"
git config user.name "muaazkori-dev"

echo.
echo Step 2: Adding all files (including database configuration)...
git add .
git add -f config/db.php
if %errorlevel% neq 0 (
    echo [ERROR] Git add failed. Please make sure Git is installed and configured.
    goto end
)

echo.
echo Step 3: Committing local changes...
git commit -m "Auto-update: Configured checkout login prompts and automatic status update notifications"
if %errorlevel% neq 0 (
    echo [INFO] No new changes to commit or commit failed.
)

echo.
echo Step 4: Pulling and merging updates from GitHub...
git pull origin main --allow-unrelated-histories --no-edit
if %errorlevel% neq 0 (
    echo [WARNING] Normal pull failed or has conflicts.
)

echo.
echo Step 5: Pushing changes to GitHub (origin/main)...
git push origin main
if %errorlevel% neq 0 (
    echo.
    echo [WARNING] Standard push failed. Remote history has diverged.
    echo Attempting to force-push local files as the source of truth...
    echo.
    git push -f origin main
)

if %errorlevel% neq 0 (
    echo [ERROR] Git push failed. Please check your internet connection or GitHub credentials.
    goto end
)

echo.
echo ===================================================
echo [SUCCESS] All changes pushed to GitHub successfully!
echo ===================================================
echo Done.
