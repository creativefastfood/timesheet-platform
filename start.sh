#!/bin/bash

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo ""
echo -e "${BLUE}===============================================${NC}"
echo -e "${BLUE}   АВТОМАТИЧЕСКИЙ ЗАПУСК СИСТЕМЫ УЧЕТА ВРЕМЕНИ${NC}"
echo -e "${BLUE}===============================================${NC}"
echo ""

# Проверяем наличие PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}[ERROR] PHP не найден в системе!${NC}"
    echo ""
    echo "Установите PHP:"
    echo "  Ubuntu/Debian: sudo apt install php php-mysql"
    echo "  CentOS/RHEL:   sudo yum install php php-mysql" 
    echo "  macOS:         brew install php"
    exit 1
fi

echo -e "${GREEN}[OK] PHP найден${NC}"
php --version
echo ""

# Проверяем наличие MySQL расширения
if ! php -m | grep -q mysql; then
    echo -e "${YELLOW}[WARNING] MySQL расширение может отсутствовать${NC}"
    echo "Установите: sudo apt install php-mysql (Ubuntu) или brew install php (macOS)"
    echo ""
fi

# Переходим в директорию скрипта
cd "$(dirname "$0")"

echo "Запускаем веб-сервер на localhost:8000..."
echo ""
echo -e "${YELLOW}После запуска откройте в браузере:${NC}"
echo -e "  ${GREEN}http://localhost:8000/install.php${NC}      - Автоматическая установка"
echo -e "  ${GREEN}http://localhost:8000/frontend/demo.html${NC} - Демо-страница"
echo -e "  ${GREEN}http://localhost:8000/frontend/index.html${NC} - Основное приложение"
echo ""
echo -e "${YELLOW}Для остановки нажмите Ctrl+C${NC}"
echo ""

# Запускаем PHP сервер
php -S localhost:8000