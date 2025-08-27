<?php
/**
 * API для интеграции с Битрикс24
 * Позволяет синхронизировать данные о рабочем времени с CRM системой
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../config/database.php';

class BitrixIntegration {
    private $conn;
    private $bitrix_webhook_url;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // URL вебхука Битрикс24 (нужно будет настроить в админпанели)
        $this->bitrix_webhook_url = 'https://your-portal.bitrix24.ru/rest/1/webhook_key/';
    }
    
    /**
     * Синхронизация сотрудников с Битрикс24
     */
    public function syncEmployeesFromBitrix() {
        $url = $this->bitrix_webhook_url . 'user.get.json';
        
        $response = $this->makeRequest($url, array(
            'FILTER' => array(
                'ACTIVE' => 'Y'
            )
        ));
        
        if ($response && isset($response['result'])) {
            $synced = 0;
            foreach ($response['result'] as $bitrix_user) {
                // Проверяем, существует ли уже такой сотрудник
                $check_query = "SELECT id FROM employees WHERE email = :email";
                $check_stmt = $this->conn->prepare($check_query);
                $check_stmt->bindParam(':email', $bitrix_user['EMAIL']);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() == 0) {
                    // Добавляем нового сотрудника
                    $insert_query = "INSERT INTO employees (full_name, email, phone) VALUES (:name, :email, :phone)";
                    $insert_stmt = $this->conn->prepare($insert_query);
                    
                    $full_name = $bitrix_user['LAST_NAME'] . ' ' . $bitrix_user['NAME'];
                    if (!empty($bitrix_user['SECOND_NAME'])) {
                        $full_name .= ' ' . $bitrix_user['SECOND_NAME'];
                    }
                    
                    $insert_stmt->bindParam(':name', $full_name);
                    $insert_stmt->bindParam(':email', $bitrix_user['EMAIL']);
                    $insert_stmt->bindParam(':phone', $bitrix_user['WORK_PHONE']);
                    
                    if ($insert_stmt->execute()) {
                        $synced++;
                    }
                }
            }
            
            return array(
                "success" => true, 
                "message" => "Синхронизировано сотрудников: " . $synced
            );
        }
        
        return array("success" => false, "message" => "Ошибка синхронизации");
    }
    
    /**
     * Отправка табеля в Битрикс24 как задачу или комментарий
     */
    public function sendTimesheetToBitrix($start_date, $end_date, $project_id = null) {
        // Получаем данные табеля
        $timesheet_data = $this->getTimesheetData($start_date, $end_date);
        
        // Формируем описание табеля
        $description = "Табель рабочего времени за период с " . $start_date . " по " . $end_date . "\n\n";
        
        $total_amount = 0;
        $grouped_data = array();
        
        foreach ($timesheet_data as $record) {
            $employee = $record['full_name'];
            if (!isset($grouped_data[$employee])) {
                $grouped_data[$employee] = array();
            }
            
            $role = $record['role_name'];
            if (!isset($grouped_data[$employee][$role])) {
                $grouped_data[$employee][$role] = array(
                    'hours' => 0,
                    'amount' => 0
                );
            }
            
            $grouped_data[$employee][$role]['hours'] += $record['hours_worked'];
            $grouped_data[$employee][$role]['amount'] += $record['total_amount'];
            $total_amount += $record['total_amount'];
        }
        
        foreach ($grouped_data as $employee => $roles) {
            $description .= $employee . ":\n";
            foreach ($roles as $role => $data) {
                $description .= "  " . $role . ": " . $data['hours'] . "ч = " . round($data['amount']) . "₽\n";
            }
            $description .= "\n";
        }
        
        $description .= "Общая сумма: " . round($total_amount) . "₽";
        
        // Создаем задачу в Битрикс24
        $url = $this->bitrix_webhook_url . 'tasks.task.add.json';
        
        $task_data = array(
            'fields' => array(
                'TITLE' => 'Табель рабочего времени ' . $start_date . ' - ' . $end_date,
                'DESCRIPTION' => $description,
                'RESPONSIBLE_ID' => 1, // ID ответственного (нужно настроить)
                'PRIORITY' => 1,
                'STATUS' => 2, // В работе
            )
        );
        
        if ($project_id) {
            $task_data['fields']['GROUP_ID'] = $project_id;
        }
        
        $response = $this->makeRequest($url, $task_data);
        
        if ($response && isset($response['result'])) {
            return array(
                "success" => true,
                "message" => "Табель отправлен в Битрикс24",
                "task_id" => $response['result']['task']['id']
            );
        }
        
        return array("success" => false, "message" => "Ошибка отправки в Битрикс24");
    }
    
    /**
     * Создание сделки на основе табеля
     */
    public function createDealFromTimesheet($start_date, $end_date, $contact_id = null) {
        $timesheet_data = $this->getTimesheetData($start_date, $end_date);
        $total_amount = array_sum(array_column($timesheet_data, 'total_amount'));
        
        $url = $this->bitrix_webhook_url . 'crm.deal.add.json';
        
        $deal_data = array(
            'fields' => array(
                'TITLE' => 'Оплата за период ' . $start_date . ' - ' . $end_date,
                'OPPORTUNITY' => $total_amount,
                'CURRENCY_ID' => 'RUB',
                'STAGE_ID' => 'NEW', // Новая сделка
                'TYPE_ID' => 'SERVICES',
                'COMMENTS' => 'Автоматически создано на основе табеля рабочего времени'
            )
        );
        
        if ($contact_id) {
            $deal_data['fields']['CONTACT_ID'] = $contact_id;
        }
        
        $response = $this->makeRequest($url, $deal_data);
        
        if ($response && isset($response['result'])) {
            return array(
                "success" => true,
                "message" => "Сделка создана в Битрикс24",
                "deal_id" => $response['result']
            );
        }
        
        return array("success" => false, "message" => "Ошибка создания сделки");
    }
    
    /**
     * Получение списка проектов/групп из Битрикс24
     */
    public function getBitrixProjects() {
        $url = $this->bitrix_webhook_url . 'sonet_group.get.json';
        
        $response = $this->makeRequest($url, array(
            'FILTER' => array(
                'ACTIVE' => 'Y'
            )
        ));
        
        if ($response && isset($response['result'])) {
            return array(
                "success" => true,
                "projects" => $response['result']
            );
        }
        
        return array("success" => false, "message" => "Ошибка получения проектов");
    }
    
    /**
     * Вспомогательная функция для получения данных табеля
     */
    private function getTimesheetData($start_date, $end_date) {
        $query = "SELECT 
                    e.full_name,
                    r.role_name,
                    r.hourly_rate,
                    w.work_date,
                    w.hours_worked,
                    (w.hours_worked * r.hourly_rate) as total_amount
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
    
    /**
     * Вспомогательная функция для HTTP запросов к Битрикс24
     */
    private function makeRequest($url, $data = array()) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            return json_decode($response, true);
        }
        
        return false;
    }
}

// Обработка API запросов
$integration = new BitrixIntegration();
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    switch ($data['action'] ?? '') {
        case 'sync_employees':
            echo json_encode($integration->syncEmployeesFromBitrix());
            break;
            
        case 'send_timesheet':
            echo json_encode($integration->sendTimesheetToBitrix(
                $data['start_date'],
                $data['end_date'],
                $data['project_id'] ?? null
            ));
            break;
            
        case 'create_deal':
            echo json_encode($integration->createDealFromTimesheet(
                $data['start_date'],
                $data['end_date'],
                $data['contact_id'] ?? null
            ));
            break;
            
        default:
            echo json_encode(array("success" => false, "message" => "Неизвестное действие"));
    }
} else if ($method == 'GET') {
    if (isset($_GET['action']) && $_GET['action'] == 'projects') {
        echo json_encode($integration->getBitrixProjects());
    } else {
        echo json_encode(array(
            "message" => "API интеграции с Битрикс24",
            "available_actions" => array(
                "sync_employees" => "Синхронизация сотрудников",
                "send_timesheet" => "Отправка табеля как задачу",
                "create_deal" => "Создание сделки на основе табеля",
                "projects" => "Получение списка проектов"
            )
        ));
    }
}