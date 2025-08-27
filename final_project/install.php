<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <title>Автоматическая установка платформы</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .step { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .step h3 { margin-top: 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .btn { 
            background: #007bff; color: white; padding: 10px 20px; 
            text-decoration: none; border-radius: 5px; display: inline-block; 
            border: none; cursor: pointer; font-size: 16px;
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>";

echo "<h1>🚀 Автоматическая установка системы учета времени</h1>";

// Конфигурация по умолчанию
$config = [
    'db_host' => 'localhost',
    'db_name' => 'timesheet_platform',
    'db_user' => 'root',
    'db_pass' => '',
    'admin_user' => 'admin',
    'admin_pass' => 'password123'
];

// Если это POST запрос - выполняем установку
if ($_POST['install'] ?? false) {
    $config = array_merge($config, $_POST);
    performInstallation($config);
} else {
    showInstallForm($config);
}

function showInstallForm($config) {
    echo "<div class='step'>
    <h3>Настройки установки</h3>
    <form method='post'>
        <table style='width: 100%;'>
            <tr>
                <td><label>Хост MySQL:</label></td>
                <td><input type='text' name='db_host' value='{$config['db_host']}' required style='width: 200px;'></td>
            </tr>
            <tr>
                <td><label>Имя базы данных:</label></td>
                <td><input type='text' name='db_name' value='{$config['db_name']}' required style='width: 200px;'></td>
            </tr>
            <tr>
                <td><label>Пользователь MySQL:</label></td>
                <td><input type='text' name='db_user' value='{$config['db_user']}' required style='width: 200px;'></td>
            </tr>
            <tr>
                <td><label>Пароль MySQL:</label></td>
                <td><input type='password' name='db_pass' value='{$config['db_pass']}' style='width: 200px;'></td>
            </tr>
            <tr>
                <td><label>Логин администратора:</label></td>
                <td><input type='text' name='admin_user' value='{$config['admin_user']}' required style='width: 200px;'></td>
            </tr>
            <tr>
                <td><label>Пароль администратора:</label></td>
                <td><input type='password' name='admin_pass' value='{$config['admin_pass']}' required style='width: 200px;'></td>
            </tr>
        </table>
        <br>
        <input type='hidden' name='install' value='1'>
        <button type='submit' class='btn'>🚀 Начать автоматическую установку</button>
    </form>
    </div>";
    
    echo "<div class='step'>
    <h3>Что будет установлено:</h3>
    <ul>
        <li>✅ Создание базы данных MySQL</li>
        <li>✅ Создание всех таблиц</li>
        <li>✅ Загрузка тестовых данных из скриншота</li>
        <li>✅ Настройка конфигурации</li>
        <li>✅ Создание пользователей системы</li>
        <li>✅ Проверка работоспособности</li>
    </ul>
    </div>";
}

function performInstallation($config) {
    echo "<h2>Процесс установки</h2>";
    
    // Шаг 1: Проверка PHP
    echo "<div class='step'>";
    echo "<h3>Шаг 1: Проверка системы</h3>";
    
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        echo "<p class='error'>❌ PHP версии 7.4+ требуется. Текущая версия: " . PHP_VERSION . "</p>";
        return;
    }
    echo "<p class='success'>✅ PHP версия: " . PHP_VERSION . "</p>";
    
    if (!extension_loaded('pdo_mysql')) {
        echo "<p class='error'>❌ PDO MySQL расширение не установлено</p>";
        return;
    }
    echo "<p class='success'>✅ PDO MySQL расширение доступно</p>";
    echo "</div>";
    
    // Шаг 2: Подключение к MySQL
    echo "<div class='step'>";
    echo "<h3>Шаг 2: Подключение к MySQL</h3>";
    
    try {
        $pdo = new PDO(
            "mysql:host={$config['db_host']}", 
            $config['db_user'], 
            $config['db_pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p class='success'>✅ Подключение к MySQL успешно</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Ошибка подключения к MySQL: " . $e->getMessage() . "</p>";
        return;
    }
    echo "</div>";
    
    // Шаг 3: Создание базы данных
    echo "<div class='step'>";
    echo "<h3>Шаг 3: Создание базы данных</h3>";
    
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$config['db_name']}`");
        echo "<p class='success'>✅ База данных '{$config['db_name']}' создана</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Ошибка создания БД: " . $e->getMessage() . "</p>";
        return;
    }
    echo "</div>";
    
    // Шаг 4: Создание таблиц
    echo "<div class='step'>";
    echo "<h3>Шаг 4: Создание таблиц</h3>";
    
    $tables_sql = "
    CREATE TABLE IF NOT EXISTS employees (
        id INT PRIMARY KEY AUTO_INCREMENT,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE,
        phone VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS roles (
        id INT PRIMARY KEY AUTO_INCREMENT,
        role_name VARCHAR(100) NOT NULL,
        hourly_rate DECIMAL(10,2) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS work_shifts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id INT NOT NULL,
        role_id INT NOT NULL,
        work_date DATE NOT NULL,
        hours_worked DECIMAL(4,2) NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id),
        FOREIGN KEY (role_id) REFERENCES roles(id),
        INDEX idx_date (work_date),
        INDEX idx_employee (employee_id)
    );
    
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        user_role ENUM('admin', 'manager', 'employee') DEFAULT 'employee',
        employee_id INT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    );";
    
    try {
        $pdo->exec($tables_sql);
        echo "<p class='success'>✅ Таблицы созданы</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Ошибка создания таблиц: " . $e->getMessage() . "</p>";
        return;
    }
    echo "</div>";
    
    // Шаг 5: Загрузка данных
    echo "<div class='step'>";
    echo "<h3>Шаг 5: Загрузка тестовых данных</h3>";
    
    try {
        // Роли
        $pdo->exec("INSERT IGNORE INTO roles (role_name, hourly_rate, description) VALUES
            ('Ассистент', 312.50, 'Основная роль ассистента'),
            ('Ассистент, переработки', 350.00, 'Ассистент в переработочное время'),
            ('Выездная', 500.00, 'Работа на выездной съемке'),
            ('Модель', 500.00, 'Работа в качестве модели'),
            ('Администратор', 375.00, 'Административные функции')");
        
        // Сотрудники
        $pdo->exec("INSERT IGNORE INTO employees (full_name, email, phone) VALUES
            ('Яна', 'yana@company.ru', '+7-900-123-4567'),
            ('Илья Полумягкий', 'ilya.polumyagkiy@company.ru', '+7-900-234-5678'),
            ('Ковач Андриана Ивановна', 'kovach.andriana@company.ru', '+7-900-345-6789'),
            ('Кузьмина Кристина', 'kuzmina.kristina@company.ru', '+7-900-456-7890'),
            ('Соколов Савелий Алексеевич', 'sokolov.saveliy@company.ru', '+7-900-567-8901')");
        
        // Рабочие смены (данные из скриншота)
        $shifts = [
            // Яна
            [1, 1, '2024-08-08', 8],
            [1, 1, '2024-08-15', 4],
            [1, 5, '2024-08-11', 9],
            [1, 5, '2024-08-12', 9],
            [1, 5, '2024-08-13', 9],
            [1, 5, '2024-08-14', 9],
            [1, 5, '2024-08-15', 9],
            [1, 5, '2024-08-19', 9],
            [1, 5, '2024-08-20', 9],
            [1, 5, '2024-08-21', 9],
            [1, 5, '2024-08-22', 9],
            
            // Илья Полумягкий
            [2, 1, '2024-08-15', 7],
            [2, 1, '2024-08-19', 8],
            [2, 1, '2024-08-20', 8],
            [2, 2, '2024-08-12', 5],
            [2, 2, '2024-08-18', 6],
            [2, 2, '2024-08-19', 9],
            [2, 2, '2024-08-21', 3],
            [2, 2, '2024-08-22', 1],
            [2, 3, '2024-08-11', 9],
            [2, 5, '2024-08-09', 4],
            [2, 5, '2024-08-20', 2],
            
            // Ковач Андриана Ивановна
            [3, 1, '2024-08-15', 7],
            [3, 2, '2024-08-11', 8],
            [3, 2, '2024-08-12', 8],
            [3, 2, '2024-08-13', 8],
            [3, 2, '2024-08-14', 8],
            [3, 3, '2024-08-09', 9],
            [3, 3, '2024-08-20', 9],
            [3, 3, '2024-08-21', 8],
            [3, 3, '2024-08-22', 7],
            [3, 4, '2024-08-16', 32],
            
            // Кузьмина Кристина
            [4, 1, '2024-08-11', 8],
            [4, 1, '2024-08-18', 2],
            [4, 2, '2024-08-11', 3],
            
            // Соколов Савелий Алексеевич
            [5, 1, '2024-08-16', 4],
            [5, 1, '2024-08-17', 3],
            [5, 2, '2024-08-11', 4],
            [5, 2, '2024-08-12', 5],
            [5, 2, '2024-08-13', 5],
            [5, 2, '2024-08-16', 2],
            [5, 2, '2024-08-17', 2],
            [5, 5, '2024-08-10', 3],
            [5, 5, '2024-08-11', 7],
            [5, 5, '2024-08-12', 3],
            [5, 5, '2024-08-13', 3],
            [5, 5, '2024-08-20', 2],
            [5, 5, '2024-08-21', 7]
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO work_shifts (employee_id, role_id, work_date, hours_worked) VALUES (?, ?, ?, ?)");
        foreach ($shifts as $shift) {
            $stmt->execute($shift);
        }
        
        // Пользователи
        $admin_hash = password_hash($config['admin_pass'], PASSWORD_DEFAULT);
        $pdo->exec("INSERT IGNORE INTO users (username, password_hash, user_role) VALUES 
            ('{$config['admin_user']}', '$admin_hash', 'admin'),
            ('manager', '$admin_hash', 'manager'),
            ('yana', '$admin_hash', 'employee')");
        
        echo "<p class='success'>✅ Тестовые данные загружены</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Ошибка загрузки данных: " . $e->getMessage() . "</p>";
        return;
    }
    echo "</div>";
    
    // Шаг 6: Обновление конфигурации
    echo "<div class='step'>";
    echo "<h3>Шаг 6: Настройка конфигурации</h3>";
    
    $config_content = "<?php
class Database {
    private \$host = '{$config['db_host']}';
    private \$db_name = '{$config['db_name']}';
    private \$username = '{$config['db_user']}';
    private \$password = '{$config['db_pass']}';
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        try {
            \$dsn = \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=utf8mb4\";
            \$this->conn = new PDO(\$dsn, \$this->username, \$this->password, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4\"
            ));
        } catch(PDOException \$exception) {
            error_log(\"Database connection error: \" . \$exception->getMessage());
            throw new Exception(\"Ошибка подключения к базе данных: \" . \$exception->getMessage());
        }
        return \$this->conn;
    }
}";
    
    if (!file_exists('backend/config')) {
        mkdir('backend/config', 0755, true);
    }
    
    if (file_put_contents('backend/config/database.php', $config_content)) {
        echo "<p class='success'>✅ Конфигурация базы данных обновлена</p>";
    } else {
        echo "<p class='error'>❌ Не удалось обновить конфигурацию</p>";
    }
    echo "</div>";
    
    // Шаг 7: Проверка работоспособности
    echo "<div class='step'>";
    echo "<h3>Шаг 7: Финальная проверка</h3>";
    
    try {
        $check_pdo = new PDO(
            "mysql:host={$config['db_host']};dbname={$config['db_name']}", 
            $config['db_user'], 
            $config['db_pass']
        );
        
        $result = $check_pdo->query("SELECT COUNT(*) as count FROM employees")->fetch();
        echo "<p class='success'>✅ Сотрудников в базе: {$result['count']}</p>";
        
        $result = $check_pdo->query("SELECT COUNT(*) as count FROM work_shifts")->fetch();
        echo "<p class='success'>✅ Рабочих смен в базе: {$result['count']}</p>";
        
        $result = $check_pdo->query("SELECT SUM(w.hours_worked * r.hourly_rate) as total 
                                    FROM work_shifts w 
                                    JOIN roles r ON w.role_id = r.id 
                                    WHERE w.work_date BETWEEN '2024-08-01' AND '2024-08-31'")->fetch();
        echo "<p class='success'>✅ Общая сумма (август 2024): " . number_format($result['total'], 0) . "₽</p>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Ошибка проверки: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Результат
    echo "<div class='step' style='background: #d4edda; border-color: #c3e6cb;'>";
    echo "<h2>🎉 Установка завершена успешно!</h2>";
    echo "<p>Система готова к использованию. Данные для входа:</p>";
    echo "<ul>";
    echo "<li><strong>Администратор:</strong> {$config['admin_user']} / {$config['admin_pass']}</li>";
    echo "<li><strong>Менеджер:</strong> manager / {$config['admin_pass']}</li>";
    echo "<li><strong>Сотрудник:</strong> yana / {$config['admin_pass']}</li>";
    echo "</ul>";
    echo "<br>";
    echo "<a href='frontend/demo.html' class='btn' target='_blank'>📖 Открыть демо-страницу</a> ";
    echo "<a href='frontend/index.html' class='btn' target='_blank'>🚀 Начать работу</a>";
    echo "</div>";
}

echo "</body></html>";
?>