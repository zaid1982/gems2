@echo off
setlocal EnableExtensions EnableDelayedExpansion

REM Grant web server write access for Code Deploy Browser (Windows / XAMPP / IIS).
REM Usage:
REM   setup_code_deploy_permissions.bat           (XAMPP / local dev — Users + SYSTEM)
REM   setup_code_deploy_permissions.bat xampp
REM   setup_code_deploy_permissions.bat iis       (IIS — IIS_IUSRS + IUSR)
REM Run from CMD. Right-click "Run as administrator" if you see Access Denied.

set "MODE=%~1"
if "%MODE%"=="" set "MODE=xampp"
if /I not "%MODE%"=="xampp" if /I not "%MODE%"=="iis" (
    echo Unknown mode "%~1". Use xampp or iis.
    exit /b 1
)

set "ROOT=%~dp0.."
pushd "%ROOT%" >nul 2>&1
if errorlevel 1 (
    echo Unable to resolve project root.
    exit /b 1
)
set "ROOT=%CD%"
popd

echo GFM GEMS Code Deploy - Windows permissions setup
echo Project root: %ROOT%
echo Mode: %MODE%
echo.
echo Granting Modify on api, js, css, maintenance (not upload/ or config.ini).
echo Run as Administrator if icacls reports Access Denied.
echo.

set "FAILED=0"

if /I "%MODE%"=="xampp" (
    REM Users (S-1-5-32-545) + SYSTEM (S-1-5-18) — typical XAMPP manual or service
    set "GRANT1=*S-1-5-32-545:(OI)(CI)M"
    set "GRANT2=*S-1-5-18:(OI)(CI)M"
) else (
    REM IIS app pool / anonymous
    set "GRANT1=IIS_IUSRS:(OI)(CI)M"
    set "GRANT2=IUSR:(OI)(CI)M"
)

for %%D in (api js css maintenance) do (
    if exist "%ROOT%\%%D" (
        echo   folder: %%D
        icacls "%ROOT%\%%D" /grant !GRANT1! /T >nul
        if errorlevel 1 set "FAILED=1"
        icacls "%ROOT%\%%D" /grant !GRANT2! /T >nul
        if errorlevel 1 set "FAILED=1"
    )
)

pushd "%ROOT%" >nul
for %%F in (*.html *.php .htaccess) do (
    if exist "%%F" (
        echo   file: %%F
        icacls "%ROOT%\%%F" /grant !GRANT1! >nul
        if errorlevel 1 set "FAILED=1"
        icacls "%ROOT%\%%F" /grant !GRANT2! >nul
        if errorlevel 1 set "FAILED=1"
    )
)
popd >nul

echo.
if "%FAILED%"=="1" (
    echo Completed with warnings. Re-run this script as Administrator.
    exit /b 1
)

echo Done. Code paths should be writable by the web server.
echo Verify in maintenance/code_deploy.html - Save a test file.
exit /b 0
