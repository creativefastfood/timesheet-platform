<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

class TimesheetAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Получить всех сотрудников
    public function getEmployees() {
        $query = "SELECT id, full_name FROM employees ORDER BY full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получить все роли
    public function getRoles() {
        $query = "SELECT id, role_name, hourly_rate FROM roles ORDER BY role_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Добавить рабочую смену
    public function addWorkShift($data) {
        $query = "INSERT INTO work_shifts (employee_id, role_id, work_date, hours_worked, notes) 
                  VALUES (:employee_id, :role_id, :work_date, :hours_worked, :notes)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':employee_id', $data['employee_id']);
        $stmt->bindParam(':role_id', $data['role_id']);
        $stmt->bindParam(':work_date', $data['work_date']);
        $stmt->bindParam(':hours_worked', $data['hours_worked']);
        $stmt->bindParam(':notes', $data['notes']);
        
        if($stmt->execute()) {
            return array("message" => "Смена успешно добавлена");
        }
        return array("message" => "Ошибка при добавлении смены");
    }
    
    // Получить табель за период
    public function getTimesheet($start_date, $end_date) {
        $query = "SELECT 
                    e.full_name,
                    r.role_name,
                    r.hourly_rate,
                    w.work_date,
                    w.hours_worked,
                    (w.hours_worked * r.hourly_rate) as total_amount,
                    w.notes
                  FROM work_shifts w
                  JOIN employees e ON w.employee_id = e.id
                  JOIN roles r ON w.role_id = r.id
                  WHERE w.work_date BETWEEN :start_date AND :end_date
                  ORDER BY e.full_name, w.work_date";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получить месячный отчет
    public function getMonthlyReport($year, $month) {
        $query = "SELECT 
                    e.full_name,
                    r.role_name,
                    SUM(w.hours_worked) as total_hours,
                    SUM(w.hours_worked * r.hourly_rate) as total_amount
                  FROM work_shifts w
                  JOIN employees e ON w.employee_id = e.id
                  JOIN roles r ON w.role_id = r.id
                  WHERE YEAR(w.work_date) = :year AND MONTH(w.work_date) = :month
                  GROUP BY e.id, r.id
                  ORDER BY e.full_name, r.role_name";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Добавить нового сотрудника
    public function addEmployee($name, $email = null, $phone = null) {
        $query = "INSERT INTO employees (full_name, email, phone) VALUES (:name, :email, :phone)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        
        if($stmt->execute()) {
            return array("message" => "Сотрудник добавлен", "id" => $this->conn->lastInsertId());
        }
        return array("message" => "Ошибка при добавлении сотрудника");
    }
}

// Обработка API запросов
$api = new TimesheetAPI();
$method = $_SERVER['REQUEST_METHOD'];

// Определяем действие из URL или параметров
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_SERVER['PATH_INFO'])) {
    $path_parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    $action = $path_parts[0] ?? '';
} else {
    // Пытаемся определить действие по URL
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $url_parts = explode('/', $url_path);
    $action = end($url_parts);
    if ($action == 'timesheet.php') {
        $action = $_GET['endpoint'] ?? '';
    }
}

try {
    switch($method) {
        case 'GET':
            if($action == 'employees') {
                echo json_encode($api->getEmployees());
            } elseif($action == 'roles') {
                echo json_encode($api->getRoles());
            } elseif($action == 'timesheet') {
                $start = $_GET['start'] ?? date('Y-m-01');
                $end = $_GET['end'] ?? date('Y-m-t');
                echo json_encode($api->getTimesheet($start, $end));
            } elseif($action == 'report') {
                $year = $_GET['year'] ?? date('Y');
                $month = $_GET['month'] ?? date('m');
                echo json_encode($api->getMonthlyReport($year, $month));
            } else {
                // По умолчанию возвращаем информацию об API
                echo json_encode(array(
                    "message" => "Timesheet API",
                    "available_endpoints" => array(
                        "GET employees" => "Список сотрудников",
                        "GET roles" => "Список ролей",
                        "GET timesheet?start=YYYY-MM-DD&end=YYYY-MM-DD" => "Табель за период",
                        "GET report?year=YYYY&month=MM" => "Месячный отчет",
                        "POST shift" => "Добавить смену",
                        "POST employee" => "Добавить сотрудника"
                    )
                ));
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            
            if($action == 'shift') {
                echo json_encode($api->addWorkShift($data));
            } elseif($action == 'employee') {
                echo json_encode($api->addEmployee($data['name'], $data['email'] ?? null, $data['phone'] ?? null));
            } else {
                echo json_encode(array("error" => "Неизвестное действие: " . $action));
            }
            break;
            
        default:
            echo json_encode(array("error" => "Метод не поддерживается"));
    }
} catch (Exception $e) {
    echo json_encode(array("error" => "Ошибка сервера: " . $e->getMessage()));
}