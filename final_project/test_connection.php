<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Тестирование подключения к базе данных</h1>";

// Тестируем подключение к базе
try {
    include_once 'backend/config/database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✅ Подключение к базе данных успешно!</p>";
        
        // Тестируем наличие таблиц
        $tables = ['employees', 'roles', 'work_shifts', 'users'];
        
        foreach ($tables as $table) {
            $query = "SELECT COUNT(*) as count FROM $table";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p>Таблица <strong>$table</strong>: " . $result['count'] . " записей</p>";
        }
        
        // Тестируем API endpoints
        echo "<h2>Тестирование API</h2>";
        
        // Тест получения сотрудников
        $query = "SELECT id, full_name FROM employees ORDER BY full_name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Сотрудники:</h3><ul>";
        foreach ($employees as $emp) {
            echo "<li>" . htmlspecialchars($emp['full_name']) . " (ID: " . $emp['id'] . ")</li>";
        }
        echo "</ul>";
        
        // Тест получения ролей
        $query = "SELECT id, role_name, hourly_rate FROM roles ORDER BY role_name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Роли:</h3><ul>";
        foreach ($roles as $role) {
            echo "<li>" . htmlspecialchars($role['role_name']) . " - " . $role['hourly_rate'] . "₽/час (ID: " . $role['id'] . ")</li>";
        }
        echo "</ul>";
        
        // Тест получения смен
        $query = "SELECT COUNT(*) as count FROM work_shifts WHERE work_date BETWEEN '2024-08-01' AND '2024-08-31'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Смены за август 2024: " . $result['count'] . " записей</h3>";
        
        // Тест API через HTTP
        echo "<h2>Тестирование HTTP API</h2>";
        
        $api_tests = [
            'employees' => 'backend/api/timesheet.php?action=employees',
            'roles' => 'backend/api/timesheet.php?action=roles',
            'timesheet' => 'backend/api/timesheet.php?action=timesheet&start=2024-08-01&end=2024-08-31'
        ];
        
        foreach ($api_tests as $test_name => $url) {
            echo "<p><strong>$test_name:</strong> ";
            echo "<a href='$url' target='_blank'>Тестировать $url</a>";
            echo "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Ошибка подключения к базе данных</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><strong>Следующие шаги:</strong></p>";
echo "<ol>";
echo "<li>Если подключение успешно, протестируйте API ссылки выше</li>";
echo "<li>Откройте <a href='frontend/index.html'>главное приложение</a></li>";
echo "<li>Откройте <a href='frontend/demo.html'>демо-страницу</a></li>";
echo "<li>Проверьте консоль браузера на наличие ошибок JavaScript</li>";
echo "</ol>";
?>