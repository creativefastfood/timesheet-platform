<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/database.php';

class AuthSystem {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($username, $password) {
        $query = "SELECT u.id, u.username, u.user_role, u.employee_id, e.full_name 
                  FROM users u 
                  LEFT JOIN employees e ON u.employee_id = e.id 
                  WHERE u.username = :username AND u.is_active = TRUE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['full_name'] = $user['full_name'];
                
                return array(
                    "success" => true,
                    "message" => "Успешный вход",
                    "user" => array(
                        "id" => $user['id'],
                        "username" => $user['username'],
                        "role" => $user['user_role'],
                        "full_name" => $user['full_name']
                    )
                );
            }
        }
        
        return array("success" => false, "message" => "Неверные учетные данные");
    }
    
    public function logout() {
        session_destroy();
        return array("success" => true, "message" => "Выход выполнен");
    }
    
    public function checkAuth() {
        if (isset($_SESSION['user_id'])) {
            return array(
                "authenticated" => true,
                "user" => array(
                    "id" => $_SESSION['user_id'],
                    "username" => $_SESSION['username'],
                    "role" => $_SESSION['user_role'],
                    "full_name" => $_SESSION['full_name']
                )
            );
        }
        
        return array("authenticated" => false);
    }
    
    public function createUser($username, $password, $role = 'employee', $employee_id = null) {
        $query = "INSERT INTO users (username, password_hash, user_role, employee_id) 
                  VALUES (:username, :password_hash, :user_role, :employee_id)";
        
        $stmt = $this->conn->prepare($query);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':user_role', $role);
        $stmt->bindParam(':employee_id', $employee_id);
        
        if ($stmt->execute()) {
            return array("success" => true, "message" => "Пользователь создан");
        }
        
        return array("success" => false, "message" => "Ошибка создания пользователя");
    }
}

$auth = new AuthSystem();
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'login':
                echo json_encode($auth->login($data['username'], $data['password']));
                break;
                
            case 'logout':
                echo json_encode($auth->logout());
                break;
                
            case 'create_user':
                echo json_encode($auth->createUser(
                    $data['username'], 
                    $data['password'], 
                    $data['role'] ?? 'employee',
                    $data['employee_id'] ?? null
                ));
                break;
                
            default:
                echo json_encode(array("success" => false, "message" => "Неизвестное действие"));
        }
    }
} else if ($method == 'GET') {
    echo json_encode($auth->checkAuth());
}