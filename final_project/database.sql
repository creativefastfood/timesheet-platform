-- Структура базы данных для учета рабочего времени ассистентов

CREATE DATABASE IF NOT EXISTS timesheet_platform;
USE timesheet_platform;

-- Таблица сотрудников
CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Таблица ролей/должностей
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(100) NOT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Вставка базовых ролей
INSERT INTO roles (role_name, hourly_rate, description) VALUES
('Ассистент', 312.50, 'Основная роль ассистента'),
('Ассистент, переработки', 350.00, 'Ассистент в переработочное время'),
('Выездная', 500.00, 'Работа на выездной съемке'),
('Модель', 500.00, 'Работа в качестве модели'),
('Администратор', 375.00, 'Административные функции');

-- Таблица рабочих смен
CREATE TABLE work_shifts (
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

-- Таблица пользователей системы (для авторизации)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_role ENUM('admin', 'manager', 'employee') DEFAULT 'employee',
    employee_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Представление для отчетов
CREATE VIEW timesheet_report AS
SELECT 
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
ORDER BY e.full_name, w.work_date;

-- Представление для месячных итогов по сотрудникам
CREATE VIEW monthly_summary AS
SELECT 
    e.id as employee_id,
    e.full_name,
    YEAR(w.work_date) as year,
    MONTH(w.work_date) as month,
    r.role_name,
    SUM(w.hours_worked) as total_hours,
    SUM(w.hours_worked * r.hourly_rate) as total_amount
FROM work_shifts w
JOIN employees e ON w.employee_id = e.id
JOIN roles r ON w.role_id = r.id
GROUP BY e.id, YEAR(w.work_date), MONTH(w.work_date), r.role_name
ORDER BY e.full_name, year, month;