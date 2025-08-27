@echo off
title Timesheet Platform Server
color 0A

echo.
echo ===============================================
echo    АВТОМАТИЧЕСКИЙ ЗАПУСК СИСТЕМЫ УЧЕТА ВРЕМЕНИ
echo ===============================================
echo.

REM Проверяем наличие PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP не найден в системе!
    echo.
    echo Установите PHP или добавьте его в PATH
    echo Скачать: https://www.php.net/downloads.php
    pause
    exit /b 1
)

echo [OK] PHP найден
php --version

echo.
echo Запускаем веб-сервер на localhost:8000...
echo.
echo После запуска откройте в браузере:
echo   http://localhost:8000/install.php      - Автоматическая установка
echo   http://localhost:8000/frontend/demo.html - Демо-страница
echo   http://localhost:8000/frontend/index.html - Основное приложение
echo.
echo Для остановки нажмите Ctrl+C
echo.

REM Запускаем PHP сервер
cd /d "%~dp0"
php -S localhost:8000

pause