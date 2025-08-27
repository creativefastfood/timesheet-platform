<?php
/**
 * Автоматический запуск встроенного веб-сервера PHP
 * Этот файл запускает сервер на localhost:8000
 */

echo "🚀 Запуск встроенного веб-сервера PHP...\n\n";

// Проверяем версию PHP
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    echo "❌ Требуется PHP 7.4 или выше. Текущая версия: " . PHP_VERSION . "\n";
    exit(1);
}

// Проверяем доступность порта
$port = 8000;
$host = 'localhost';

echo "📋 Информация:\n";
echo "   Хост: $host\n";
echo "   Порт: $port\n";
echo "   PHP версия: " . PHP_VERSION . "\n\n";

// Показываем важные ссылки
echo "🔗 После запуска откройте в браузере:\n";
echo "   📦 Установка:     http://$host:$port/install.php\n";
echo "   📖 Демо:          http://$host:$port/frontend/demo.html\n";
echo "   🚀 Приложение:    http://$host:$port/frontend/index.html\n\n";

echo "⚠️  Для остановки нажмите Ctrl+C\n\n";
echo "🔄 Запускаем сервер...\n\n";

// Запускаем встроенный сервер PHP
$command = "php -S $host:$port";
echo "Команда: $command\n\n";

// Переходим в директорию проекта
chdir(__DIR__);

// Запускаем сервер
exec($command);
?>