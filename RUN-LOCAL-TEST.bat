@echo off
REM ======================================================================
REM  GameCraft Studio - run a local test server
REM
REM  Double-click this file to open the site on your own machine.
REM  To stop it: close this black window (or press Ctrl + C).
REM
REM  This is for local testing only. There is no need to upload it.
REM ======================================================================

setlocal

set "PHP=C:\Users\ADMIN\gamecraft-php\php.exe"
set "PORT=8000"

if not exist "%PHP%" (
    echo.
    echo  [ERROR] PHP was not found at:
    echo          %PHP%
    echo.
    echo  Download a portable copy of PHP, unzip it into that folder,
    echo  or edit the "set PHP=" line in this file to point at your own PHP.
    echo.
    pause
    exit /b 1
)

cd /d "%~dp0"

echo.
echo  ============================================
echo   GameCraft Studio is running
echo  ============================================
echo.
echo   Open:  http://localhost:%PORT%
echo.
echo   To stop: close this window
echo.
echo  ============================================
echo.

start "" "http://localhost:%PORT%"

"%PHP%" -S localhost:%PORT% dev-server.php

endlocal
