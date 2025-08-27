// Конфигурация API
const API_BASE = '../backend/api/timesheet.php';

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('Страница загружена, инициализируем...');
    
    // Проверяем API перед загрузкой данных
    testAPI().then(() => {
        loadEmployees();
        loadRoles();
        initializeDates();
        initializeYears();
    }).catch(error => {
        console.error('API недоступен:', error);
        showError('API недоступен. Проверьте настройки сервера.');
    });
    
    // Обработчики форм
    document.getElementById('shiftForm').addEventListener('submit', addShift);
    document.getElementById('employeeForm').addEventListener('submit', addEmployee);
});

// Тестирование API
async function testAPI() {
    try {
        console.log('Тестируем API соединение...');
        const response = await fetch(API_BASE);
        const data = await response.json();
        console.log('API ответ:', data);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return data;
    } catch (error) {
        console.error('Ошибка тестирования API:', error);
        throw error;
    }
}

// Показать ошибку пользователю
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger';
    errorDiv.innerHTML = `
        <strong>Ошибка:</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(errorDiv, container.firstChild);
}

// Загрузка сотрудников
async function loadEmployees() {
    try {
        const response = await fetch(`${API_BASE}?action=employees`);
        const data = await response.json();
        
        console.log('Response from employees API:', data);
        
        // Проверяем есть ли ошибка
        if (data.error) {
            console.error('API Error:', data.error);
            alert('Ошибка API: ' + data.error);
            return;
        }
        
        const employees = Array.isArray(data) ? data : [];
        
        const select = document.getElementById('employee');
        select.innerHTML = '<option value="">Выберите сотрудника</option>';
        
        employees.forEach(employee => {
            select.innerHTML += `<option value="${employee.id}">${employee.full_name}</option>`;
        });
        
        updateEmployeesList(employees);
    } catch (error) {
        console.error('Ошибка загрузки сотрудников:', error);
    }
}

// Загрузка ролей
async function loadRoles() {
    try {
        const response = await fetch(`${API_BASE}?action=roles`);
        const data = await response.json();
        
        console.log('Response from roles API:', data);
        
        // Проверяем есть ли ошибка
        if (data.error) {
            console.error('API Error:', data.error);
            alert('Ошибка API: ' + data.error);
            return;
        }
        
        const roles = Array.isArray(data) ? data : [];
        
        const select = document.getElementById('role');
        select.innerHTML = '<option value="">Выберите роль</option>';
        
        roles.forEach(role => {
            select.innerHTML += `<option value="${role.id}">${role.role_name} (${role.hourly_rate}₽/ч)</option>`;
        });
    } catch (error) {
        console.error('Ошибка загрузки ролей:', error);
    }
}

// Инициализация дат
function initializeDates() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    document.getElementById('workDate').value = formatDate(today);
    document.getElementById('startDate').value = formatDate(firstDay);
    document.getElementById('endDate').value = formatDate(lastDay);
}

// Инициализация годов
function initializeYears() {
    const currentYear = new Date().getFullYear();
    const yearSelect = document.getElementById('reportYear');
    
    for (let year = currentYear - 2; year <= currentYear + 1; year++) {
        yearSelect.innerHTML += `<option value="${year}" ${year === currentYear ? 'selected' : ''}>${year}</option>`;
    }
    
    document.getElementById('reportMonth').value = new Date().getMonth() + 1;
}

// Добавление смены
async function addShift(event) {
    event.preventDefault();
    
    const formData = {
        employee_id: document.getElementById('employee').value,
        role_id: document.getElementById('role').value,
        work_date: document.getElementById('workDate').value,
        hours_worked: document.getElementById('hours').value,
        notes: document.getElementById('notes').value
    };
    
    try {
        const response = await fetch(`${API_BASE}?action=shift`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        alert(result.message);
        
        if (result.message === 'Смена успешно добавлена') {
            document.getElementById('shiftForm').reset();
            document.getElementById('workDate').value = formatDate(new Date());
        }
    } catch (error) {
        console.error('Ошибка добавления смены:', error);
        alert('Ошибка при добавлении смены');
    }
}

// Добавление сотрудника
async function addEmployee(event) {
    event.preventDefault();
    
    const formData = {
        name: document.getElementById('employeeName').value,
        email: document.getElementById('employeeEmail').value || null,
        phone: document.getElementById('employeePhone').value || null
    };
    
    try {
        const response = await fetch(`${API_BASE}?action=employee`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        alert(result.message);
        
        if (result.id) {
            document.getElementById('employeeForm').reset();
            loadEmployees();
        }
    } catch (error) {
        console.error('Ошибка добавления сотрудника:', error);
        alert('Ошибка при добавлении сотрудника');
    }
}

// Загрузка табеля
async function loadTimesheet() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        alert('Выберите период');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}?action=timesheet&start=${startDate}&end=${endDate}`);
        const data = await response.json();
        
        console.log('Response from timesheet API:', data);
        
        // Проверяем есть ли ошибка
        if (data.error) {
            console.error('API Error:', data.error);
            alert('Ошибка API: ' + data.error);
            return;
        }
        
        displayTimesheet(data);
    } catch (error) {
        console.error('Ошибка загрузки табеля:', error);
        alert('Ошибка загрузки данных');
    }
}

// Отображение табеля
function displayTimesheet(data) {
    const container = document.getElementById('timesheetTable');
    
    if (data.length === 0) {
        container.innerHTML = '<p class="text-muted">Нет данных за выбранный период</p>';
        return;
    }
    
    // Группировка по сотрудникам
    const grouped = {};
    data.forEach(item => {
        if (!grouped[item.full_name]) {
            grouped[item.full_name] = {};
        }
        if (!grouped[item.full_name][item.role_name]) {
            grouped[item.full_name][item.role_name] = {
                rate: item.hourly_rate,
                hours: 0,
                amount: 0,
                days: []
            };
        }
        grouped[item.full_name][item.role_name].hours += parseFloat(item.hours_worked);
        grouped[item.full_name][item.role_name].amount += parseFloat(item.total_amount);
        grouped[item.full_name][item.role_name].days.push({
            date: item.work_date,
            hours: item.hours_worked,
            notes: item.notes
        });
    });
    
    let html = `
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ФИО</th>
                    <th>Роль</th>
                    <th>Ставка</th>
                    <th>Часов</th>
                    <th>К начислению</th>
                    <th>Детали</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    let totalAmount = 0;
    
    Object.keys(grouped).forEach(employeeName => {
        Object.keys(grouped[employeeName]).forEach(roleName => {
            const roleData = grouped[employeeName][roleName];
            totalAmount += roleData.amount;
            
            html += `
                <tr>
                    <td>${employeeName}</td>
                    <td>${roleName}</td>
                    <td>${roleData.rate}₽</td>
                    <td>${roleData.hours}</td>
                    <td><strong>${Math.round(roleData.amount)}₽</strong></td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" onclick="showDetails('${employeeName}', '${roleName}', ${JSON.stringify(roleData.days).replace(/"/g, '&quot;')})">
                            <i class="bi bi-eye"></i> Детали
                        </button>
                    </td>
                </tr>
            `;
        });
    });
    
    html += `
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <th colspan="4">Итого:</th>
                    <th>${Math.round(totalAmount)}₽</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    `;
    
    container.innerHTML = html;
}

// Загрузка месячного отчета
async function loadMonthlyReport() {
    const year = document.getElementById('reportYear').value;
    const month = document.getElementById('reportMonth').value;
    
    try {
        const response = await fetch(`${API_BASE}?action=report&year=${year}&month=${month}`);
        const data = await response.json();
        
        console.log('Response from report API:', data);
        
        // Проверяем есть ли ошибка  
        if (data.error) {
            console.error('API Error:', data.error);
            alert('Ошибка API: ' + data.error);
            return;
        }
        
        displayMonthlyReport(data);
    } catch (error) {
        console.error('Ошибка загрузки отчета:', error);
        alert('Ошибка загрузки отчета');
    }
}

// Отображение месячного отчета
function displayMonthlyReport(data) {
    const container = document.getElementById('monthlyReportTable');
    
    if (data.length === 0) {
        container.innerHTML = '<p class="text-muted">Нет данных за выбранный месяц</p>';
        return;
    }
    
    let html = `
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ФИО</th>
                    <th>Роль</th>
                    <th>Часов</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    let totalAmount = 0;
    let totalHours = 0;
    
    data.forEach(item => {
        totalAmount += parseFloat(item.total_amount);
        totalHours += parseFloat(item.total_hours);
        
        html += `
            <tr>
                <td>${item.full_name}</td>
                <td>${item.role_name}</td>
                <td>${item.total_hours}</td>
                <td><strong>${Math.round(item.total_amount)}₽</strong></td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <th colspan="2">Итого:</th>
                    <th>${totalHours}</th>
                    <th>${Math.round(totalAmount)}₽</th>
                </tr>
            </tfoot>
        </table>
    `;
    
    container.innerHTML = html;
}

// Обновление списка сотрудников
function updateEmployeesList(employees) {
    const container = document.getElementById('employeesList');
    
    let html = `
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ФИО</th>
                    <th>Email</th>
                    <th>Телефон</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    employees.forEach(employee => {
        html += `
            <tr>
                <td>${employee.full_name}</td>
                <td>${employee.email || '-'}</td>
                <td>${employee.phone || '-'}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// Показать детали работы
function showDetails(employeeName, roleName, days) {
    let details = `Детали работы: ${employeeName} - ${roleName}\n\n`;
    
    days.forEach(day => {
        details += `${formatDateRu(day.date)}: ${day.hours}ч`;
        if (day.notes) {
            details += ` (${day.notes})`;
        }
        details += '\n';
    });
    
    alert(details);
}

// Экспорт в Excel
function exportToExcel() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        alert('Выберите период для экспорта');
        return;
    }
    
    // Открываем экспорт в новом окне
    const exportUrl = `../backend/export/excel.php?action=timesheet&start=${startDate}&end=${endDate}`;
    window.open(exportUrl, '_blank');
}

// Экспорт календарного табеля
function exportCalendarTimesheet() {
    const year = document.getElementById('reportYear').value;
    const month = document.getElementById('reportMonth').value;
    
    const exportUrl = `../backend/export/excel.php?action=calendar&year=${year}&month=${month}`;
    window.open(exportUrl, '_blank');
}

// Экспорт сводного отчета
function exportSummaryReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        alert('Выберите период для экспорта');
        return;
    }
    
    const exportUrl = `../backend/export/excel.php?action=summary&start=${startDate}&end=${endDate}`;
    window.open(exportUrl, '_blank');
}

// Вспомогательные функции
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function formatDateRu(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU');
}