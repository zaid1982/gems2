@echo off
REM GEMS2 Log Directory Setup Script for Windows Server
REM Run this script as Administrator to set up log directories

echo.
echo ================================================
echo GEMS2 Log Directory Setup for Windows Server
echo ================================================
echo.

REM Get the current directory (should be the maintenance folder)
set MAINTENANCE_DIR=%~dp0
set LOG_DIR=%MAINTENANCE_DIR%logs

echo Current directory: %MAINTENANCE_DIR%
echo Log directory will be: %LOG_DIR%
echo.

REM Create log directories
echo Creating log directories...
if not exist "%LOG_DIR%" (
    mkdir "%LOG_DIR%"
    echo ✓ Created: %LOG_DIR%
) else (
    echo ✓ Already exists: %LOG_DIR%
)

if not exist "%LOG_DIR%\debug" (
    mkdir "%LOG_DIR%\debug"
    echo ✓ Created: %LOG_DIR%\debug
) else (
    echo ✓ Already exists: %LOG_DIR%\debug
)

if not exist "%LOG_DIR%\error" (
    mkdir "%LOG_DIR%\error"
    echo ✓ Created: %LOG_DIR%\error
) else (
    echo ✓ Already exists: %LOG_DIR%\error
)

echo.
echo Setting permissions for IIS...

REM Grant IIS_IUSRS full control over log directories
icacls "%LOG_DIR%" /grant "IIS_IUSRS:(OI)(CI)F" /T > nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Permissions set for IIS_IUSRS
) else (
    echo ⚠ Warning: Could not set IIS permissions. You may need to run as Administrator.
)

REM Grant IUSR read/write access
icacls "%LOG_DIR%" /grant "IUSR:(OI)(CI)M" /T > nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Permissions set for IUSR
) else (
    echo ⚠ Warning: Could not set IUSR permissions.
)

echo.
echo Creating sample log files...

REM Create sample debug log
if not exist "%LOG_DIR%\debug\debug_sample.log" (
    echo [%date% %time%] DEBUG: Log system initialized > "%LOG_DIR%\debug\debug_sample.log"
    echo [%date% %time%] DEBUG: Sample debug message >> "%LOG_DIR%\debug\debug_sample.log"
    echo ✓ Created sample debug log
)

REM Create sample error log
if not exist "%LOG_DIR%\error\error_sample.log" (
    echo [%date% %time%] ERROR: Sample error message > "%LOG_DIR%\error\error_sample.log"
    echo [%date% %time%] ERROR: This is a test error entry >> "%LOG_DIR%\error\error_sample.log"
    echo ✓ Created sample error log
)

echo.
echo ================================================
echo Setup Complete!
echo ================================================
echo.
echo Log directories created at:
echo   Debug logs: %LOG_DIR%\debug
echo   Error logs: %LOG_DIR%\error
echo.
echo To test the setup:
echo 1. Open your web browser
echo 2. Navigate to: http://localhost/gems2/maintenance/log_explorer.html
echo 3. You should see the debug and error directories
echo.
echo Configuration in config.ini should be:
echo   log_dir = ./logs
echo.
echo Press any key to exit...
pause > nul
