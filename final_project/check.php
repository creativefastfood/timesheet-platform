<?php
/**
 * Быстрая проверка системы перед использованием
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Проверка системы</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 0 auto; padding: 20px; }
        .ok { color: green; } .error { color: red; } .warning { color: orange; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>

<h1>🔍 Проверка системы</h1>

<?php
$checks = [];

// Проверка PHP
$checks['php_version'] = version_compare(PHP_VERSION, '7.4.0', '>=');
echo "<div class='box'>";
echo $checks['php_version'] ? 
    "<p class='ok'>✅ PHP версия: " . PHP_VERSION . " (ОК)</p>" : 
    "<p class='error'>❌ PHP версия слишком старая: " . PHP_VERSION . "</p>";
echo "</div>";

// Проверка MySQL расширения
$checks['mysql_ext'] = extension_loaded('pdo_mysql');
echo "<div class='box'>";
echo $checks['mysql_ext'] ? 
    "<p class='ok'>✅ MySQL расширение установлено</p>" : 
    "<p class='error'>❌ MySQL расширение отсутствует</p>";
echo "</div>";

// Проверка файлов
$required_files = [
    'backend/api/timesheet.php' => 'API файл',
    'frontend/index.html' => 'Главная страница',
    'database.sql' => 'SQL схема',
    'sample_data.sql' => 'Тестовые данные'
];

echo "<div class='box'><h3>Файлы проекта:</h3>";
$files_ok = true;
foreach ($required_files as $file => $desc) {
    if (file_exists($file)) {
        echo "<p class='ok'>✅ $desc ($file)</p>";
    } else {
        echo "<p class='error'>❌ Отсутствует: $desc ($file)</p>";
        $files_ok = false;
    }
}
$checks['files'] = $files_ok;
echo "</div>";

// Проверка доступности MySQL (если конфиг существует)
if (file_exists('backend/config/database.php')) {
    include_once 'backend/config/database.php';
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $checks['db_connection'] = true;
        echo "<div class='box'><p class='ok'>✅ Подключение к базе данных работает</p></div>";
        
        // Проверяем есть ли данные
        $stmt = $conn->query("SELECT COUNT(*) as count FROM employees");
        $emp_count = $stmt->fetch()['count'];
        
        if ($emp_count > 0) {
            echo "<div class='box'><p class='ok'>✅ В базе есть $emp_count сотрудников</p></div>";
            $checks['has_data'] = true;
        } else {
            echo "<div class='box'><p class='warning'>⚠️ База пустая, нужно загрузить данные</p></div>";
            $checks['has_data'] = false;
        }
        
    } catch (Exception $e) {
        echo "<div class='box'><p class='error'>❌ Ошибка БД: " . $e->getMessage() . "</p></div>";
        $checks['db_connection'] = false;
        $checks['has_data'] = false;
    }
} else {
    echo "<div class='box'><p class='warning'>⚠️ Конфигурация БД не найдена</p></div>";
    $checks['db_connection'] = false;
    $checks['has_data'] = false;
}

// Итоговый статус
$all_ok = array_reduce($checks, function($carry, $item) {
    return $carry && $item;
}, true);

echo "<div class='box' style='background: " . ($all_ok ? "#d4edda" : "#f8d7da") . "'>";
if ($all_ok) {
    echo "<h2 class='ok'>🎉 Всё готово к работе!</h2>";
    echo "<p><a href='frontend/index.html'>🚀 Запустить приложение</a></p>";
} else {
    echo "<h2 class='error'>❌ Нужно исправить проблемы</h2>";
    if (!$checks['db_connection'] || !$checks['has_data']) {
        echo "<p><a href='install.php'>🔧 Запустить автоматическую установку</a></p>";
    }
}
echo "</div>";

?>

</body>
</html>