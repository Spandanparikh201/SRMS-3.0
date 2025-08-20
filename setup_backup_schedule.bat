@echo off
echo ========================================
echo SRMS Auto Backup Schedule Setup
echo ========================================
echo.

REM Get the current directory
set SCRIPT_DIR=%~dp0
set PHP_PATH=C:\xampp\php\php.exe
set SCHEDULER_SCRIPT=%SCRIPT_DIR%auto_backup_scheduler.php

echo Current directory: %SCRIPT_DIR%
echo PHP Path: %PHP_PATH%
echo Scheduler Script: %SCHEDULER_SCRIPT%
echo.

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo Please update the PHP_PATH variable in this script
    pause
    exit /b 1
)

echo Creating Windows Task Scheduler entries...
echo.

REM Create checkpoint task (every 4 hours)
echo Creating checkpoint task (runs every 4 hours)...
schtasks /create /tn "SRMS_Auto_Checkpoint" /tr "\"%PHP_PATH%\" \"%SCHEDULER_SCRIPT%\" checkpoint" /sc hourly /mo 4 /st 08:00 /f
if %errorlevel% equ 0 (
    echo ✓ Checkpoint task created successfully
) else (
    echo ✗ Failed to create checkpoint task
)

REM Create weekly backup task (Sundays at 2 AM)
echo Creating weekly backup task (runs Sundays at 2:00 AM)...
schtasks /create /tn "SRMS_Weekly_Backup" /tr "\"%PHP_PATH%\" \"%SCHEDULER_SCRIPT%\" weekly" /sc weekly /d SUN /st 02:00 /f
if %errorlevel% equ 0 (
    echo ✓ Weekly backup task created successfully
) else (
    echo ✗ Failed to create weekly backup task
)

echo.
echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo The following tasks have been created:
echo.
echo 1. SRMS_Auto_Checkpoint
echo    - Runs every 4 hours starting at 8:00 AM
echo    - Creates automatic database checkpoints
echo.
echo 2. SRMS_Weekly_Backup  
echo    - Runs every Sunday at 2:00 AM
echo    - Creates full database backups
echo.
echo You can view and manage these tasks in:
echo Windows Task Scheduler ^> Task Scheduler Library
echo.
echo To remove the tasks later, run:
echo schtasks /delete /tn "SRMS_Auto_Checkpoint" /f
echo schtasks /delete /tn "SRMS_Weekly_Backup" /f
echo.

pause