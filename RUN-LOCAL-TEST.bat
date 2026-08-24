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

REM  Leave this as "php" to use the PHP already on your PATH,
REM  or replace it with a full path such as C:\php\php.exe
set "PHP=php"
set "PORT=8000"

REM  Accept either a command on the PATH or a full path to php.exe
set "PHPOK="
if exist "%PHP%" set "PHPOK=1"
where "%PHP%" >nul 2>&1 && set "PHPOK=1"

if not defined PHPOK (
    echo.
    echo  [ERROR] PHP was not found:
    echo          %PHP%
    echo.
    echo  Install PHP 8.1 or newer and make sure it is on your PATH,
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
