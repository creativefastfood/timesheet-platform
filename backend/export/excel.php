<?php
/**
 * Экспорт данных табеля в Excel формат
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/database.php';

class ExcelExport {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Экспорт табеля в CSV формат (совместимо с Excel)
     */
    public function exportTimesheet($start_date, $end_date, $format = 'csv') {
        $data = $this->getTimesheetData($start_date, $end_date);
        
        if ($format == 'csv') {
            $this->exportToCSV($data, $start_date, $end_date);
        } else if ($format == 'json') {
            $this->exportToJSON($data);
        }
    }
    
    /**
     * Экспорт в CSV формат
     */
    private function exportToCSV($data, $start_date, $end_date) {
        $filename = "timesheet_" . $start_date . "_" . $end_date . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM для корректного отображения русских символов в Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Заголовок
        fputcsv($output, array(
            'ФИО',
            'Роль',
            'Почасовая ставка',
            'Дата',
            'Часов отработано',
            'К начислению',
            'Примечания'
        ), ';');
        
        // Данные
        foreach ($data as $row) {
            fputcsv($output, array(
                $row['full_name'],
                $row['role_name'],
                $row['hourly_rate'] . '₽',
                date('d.m.Y', strtotime($row['work_date'])),
                $row['hours_worked'],
                round($row['total_amount']) . '₽',
                $row['notes'] ?? ''
            ), ';');
        }
        
        // Итоговая строка
        $total_amount = array_sum(array_column($data, 'total_amount'));
        $total_hours = array_sum(array_column($data, 'hours_worked'));
        
        fputcsv($output, array('', '', '', 'ИТОГО:', $total_hours, round($total_amount) . '₽', ''), ';');
        
        fclose($output);
        exit;
    }
    
    /**
     * Экспорт сводного отчета по сотрудникам
     */
    public function exportEmployeeSummary($start_date, $end_date) {
        $query = "SELECT 
                    e.full_name,
                    r.role_name,
                    r.hourly_rate,
                    SUM(w.hours_worked) as total_hours,
                    SUM(w.hours_worked * r.hourly_rate) as total_amount,
                    COUNT(DISTINCT w.work_date) as work_days
                  FROM work_shifts w
                  JOIN employees e ON w.employee_id = e.id
                  JOIN roles r ON w.role_id = r.id
                  WHERE w.work_date BETWEEN :start_date AND :end_date
                  GROUP BY e.id, r.id
                  ORDER BY e.full_name, r.role_name";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filename = "summary_" . $start_date . "_" . $end_date . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM для корректного отображения русских символов в Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Заголовок
        fputcsv($output, array(
            'ФИО',
            'Роль',
            'Ставка',
            'Всего часов',
            'Рабочих дней',
            'К начислению'
        ), ';');
        
        $grand_total = 0;
        $grand_hours = 0;
        
        foreach ($data as $row) {
            fputcsv($output, array(
                $row['full_name'],
                $row['role_name'],
                $row['hourly_rate'] . '₽',
                $row['total_hours'],
                $row['work_days'],
                round($row['total_amount']) . '₽'
            ), ';');
            
            $grand_total += $row['total_amount'];
            $grand_hours += $row['total_hours'];
        }
        
        // Итоговая строка
        fputcsv($output, array('', '', 'ИТОГО:', $grand_hours, '', round($grand_total) . '₽'), ';');
        
        fclose($output);
        exit;
    }
    
    /**
     * Календарный табель (как в оригинальной таблице)
     */
    public function exportCalendarTimesheet($year, $month) {
        // Получаем все смены за месяц
        $start_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $query = "SELECT 
                    e.full_name,
                    r.role_name,
                    r.hourly_rate,
                    w.work_date,
                    w.hours_worked,
                    (w.hours_worked * r.hourly_rate) as amount
                  FROM work_shifts w
                  JOIN employees e ON w.employee_id = e.id
                  JOIN roles r ON w.role_id = r.id
                  WHERE w.work_date BETWEEN :start_date AND :end_date
                  ORDER BY e.full_name, r.role_name, w.work_date";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Группируем данные
        $calendar_data = array();
        $days_in_month = date('t', strtotime($start_date));
        
        foreach ($data as $row) {
            $key = $row['full_name'] . '|' . $row['role_name'];
            if (!isset($calendar_data[$key])) {
                $calendar_data[$key] = array(
                    'name' => $row['full_name'],
                    'role' => $row['role_name'],
                    'rate' => $row['hourly_rate'],
                    'days' => array_fill(1, $days_in_month, ''),
                    'total_hours' => 0,
                    'total_amount' => 0
                );
            }
            
            $day = (int)date('d', strtotime($row['work_date']));
            $calendar_data[$key]['days'][$day] = $row['hours_worked'];
            $calendar_data[$key]['total_hours'] += $row['hours_worked'];
            $calendar_data[$key]['total_amount'] += $row['amount'];
        }
        
        $filename = "calendar_" . $year . "_" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM для корректного отображения русских символов в Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Заголовки
        $header = array('ФИО', 'Роль', 'Ставка');
        for ($i = 1; $i <= $days_in_month; $i++) {
            $header[] = $i;
        }
        $header[] = 'Всего часов';
        $header[] = 'К начислению';
        
        fputcsv($output, $header, ';');
        
        // Данные
        $grand_total = 0;
        
        foreach ($calendar_data as $row) {
            $csv_row = array($row['name'], $row['role'], $row['rate'] . '₽');
            
            for ($i = 1; $i <= $days_in_month; $i++) {
                $csv_row[] = $row['days'][$i];
            }
            
            $csv_row[] = $row['total_hours'];
            $csv_row[] = round($row['total_amount']) . '₽';
            
            fputcsv($output, $csv_row, ';');
            $grand_total += $row['total_amount'];
        }
        
        // Итоговая строка
        $total_row = array_fill(0, 3 + $days_in_month, '');
        $total_row[0] = 'ОБЩАЯ СУММА';
        $total_row[count($total_row) - 1] = round($grand_total) . '₽';
        fputcsv($output, $total_row, ';');
        
        fclose($output);
        exit;
    }
    
    /**
     * Экспорт в JSON (для интеграций)
     */
    private function exportToJSON($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Получение данных табеля
     */
    private function getTimesheetData($start_date, $end_date) {
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
}

// Обработка запросов
$export = new ExcelExport();

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'timesheet':
            $start_date = $_GET['start'] ?? date('Y-m-01');
            $end_date = $_GET['end'] ?? date('Y-m-t');
            $format = $_GET['format'] ?? 'csv';
            $export->exportTimesheet($start_date, $end_date, $format);
            break;
            
        case 'summary':
            $start_date = $_GET['start'] ?? date('Y-m-01');
            $end_date = $_GET['end'] ?? date('Y-m-t');
            $export->exportEmployeeSummary($start_date, $end_date);
            break;
            
        case 'calendar':
            $year = $_GET['year'] ?? date('Y');
            $month = $_GET['month'] ?? date('m');
            $export->exportCalendarTimesheet($year, $month);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(array(
                "message" => "API экспорта данных",
                "available_actions" => array(
                    "timesheet" => "Экспорт детального табеля (CSV)",
                    "summary" => "Экспорт сводного отчета (CSV)",
                    "calendar" => "Экспорт календарного табеля (CSV)"
                ),
                "parameters" => array(
                    "start" => "Дата начала (YYYY-MM-DD)",
                    "end" => "Дата окончания (YYYY-MM-DD)",
                    "year" => "Год (для календарного табеля)",
                    "month" => "Месяц (для календарного табеля)",
                    "format" => "Формат (csv, json)"
                )
            ));
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Не указано действие"));
}