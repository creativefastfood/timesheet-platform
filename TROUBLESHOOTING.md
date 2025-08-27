# 🔧 Устранение неисправностей

## Проблема: "Ошибка загрузки данных"

### Шаг 1: Проверьте базу данных
```bash
# Убедитесь что база данных создана и данные загружены
mysql -u root -p -e "USE timesheet_platform; SELECT COUNT(*) FROM employees;"
```

Должно вернуть `5` (количество сотрудников из скриншота).

### Шаг 2: Проверьте подключение
Откройте в браузере:
```
http://your-server/timesheet-platform/test_connection.php
```

Вы должны увидеть зеленые галочки и данные из таблиц.

### Шаг 3: Проверьте API
Откройте в браузере каждую из ссылок:

1. **Сотрудники**: `backend/api/timesheet.php?action=employees`
   - Должен вернуть JSON с массивом сотрудников

2. **Роли**: `backend/api/timesheet.php?action=roles` 
   - Должен вернуть массив с ролями и ставками

3. **Табель**: `backend/api/timesheet.php?action=timesheet&start=2024-08-01&end=2024-08-31`
   - Должен вернуть данные смен за август

### Шаг 4: Проверьте консоль браузера
1. Откройте `frontend/index.html`
2. Нажмите F12 (Developer Tools)
3. Перейдите на вкладку "Console" 
4. Обновите страницу
5. Посмотрите на ошибки

### Типичные ошибки и решения:

#### ❌ "Failed to fetch" 
**Проблема**: Неправильный путь к API или сервер недоступен  
**Решение**: 
- Проверьте что все файлы лежат в правильных папках
- Убедитесь что PHP работает на сервере
- Проверьте права доступа к папкам

#### ❌ "CORS error"
**Проблема**: Блокировка CORS политикой браузера  
**Решение**: 
- Запускайте через веб-сервер (не file://)
- Используйте localhost или реальный домен

#### ❌ "Connection failed"
**Проблема**: Неверные данные подключения к БД  
**Решение**: 
- Проверьте `backend/config/database.php`
- Убедитесь что MySQL запущен
- Проверьте имя пользователя/пароль

#### ❌ "Table doesn't exist"
**Проблема**: База данных не создана  
**Решение**:
```bash
mysql -u root -p < database.sql
mysql -u root -p timesheet_platform < sample_data.sql
```

## Быстрая диагностика

### Проверка 1: Файлы на месте
```bash
ls -la timesheet-platform/
# Должны быть папки: backend, frontend, файлы: database.sql, sample_data.sql
```

### Проверка 2: PHP работает
```bash
php -v
# Должен показать версию PHP 7.4+
```

### Проверка 3: MySQL работает
```bash
mysql -u root -p -e "SHOW DATABASES;"
# Должен показать список БД включая timesheet_platform
```

### Проверка 4: API отвечает
```bash
curl "http://localhost/timesheet-platform/backend/api/timesheet.php?action=employees"
# Должен вернуть JSON с сотрудниками
```

## Альтернативные варианты запуска

### Вариант 1: Встроенный сервер PHP
```bash
cd timesheet-platform
php -S localhost:8000
```
Откройте: http://localhost:8000/frontend/index.html

### Вариант 2: Docker (если есть)
```bash
# Создайте docker-compose.yml
version: '3'
services:
  web:
    image: php:8.0-apache
    volumes:
      - .:/var/www/html
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
```

### Вариант 3: XAMPP/MAMP
1. Скопируйте папку в htdocs
2. Запустите Apache + MySQL
3. Импортируйте SQL файлы через phpMyAdmin

## Логи для отладки

### PHP логи:
```bash
tail -f /var/log/apache2/error.log
# или
tail -f /var/log/php_errors.log
```

### MySQL логи:
```bash
tail -f /var/log/mysql/error.log
```

### Браузер:
- F12 → Console (JavaScript ошибки)
- F12 → Network (HTTP запросы)

## Контрольный список

- [ ] MySQL запущен и доступен
- [ ] База данных `timesheet_platform` создана  
- [ ] Таблицы созданы из `database.sql`
- [ ] Тестовые данные загружены из `sample_data.sql`
- [ ] PHP может подключиться к БД
- [ ] API файлы доступны через веб-сервер
- [ ] CORS настроен корректно
- [ ] JavaScript может обратиться к API
- [ ] Консоль браузера не показывает ошибок

## Если ничего не помогло

1. **Проверьте версии**:
   - PHP 7.4+ ✅
   - MySQL 5.7+ ✅  
   - Современный браузер ✅

2. **Попробуйте простейший тест**:
   ```php
   <?php
   phpinfo();
   ?>
   ```

3. **Создайте минимальный API тест**:
   ```php
   <?php
   header('Content-Type: application/json');
   echo json_encode(['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
   ?>
   ```

4. **Обратитесь к системному администратору** для настройки веб-сервера