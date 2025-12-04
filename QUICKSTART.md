# Быстрый старт проекта

## Что нужно сделать вручную

### 1. Установить PHP и Composer (если ещё не установлены)

- **PHP 8.1+**: https://windows.php.net/download/
  - Распакуй в `C:\php`
  - Добавь `C:\php` в PATH
  - Включи расширение `pdo_pgsql` в `php.ini`

- **Composer**: https://getcomposer.org/Composer-Setup.exe
  - Запусти установщик

### 2. Запустить Docker Desktop

- Убедись, что Docker Desktop запущен (иконка в трее)

### 3. Запустить PostgreSQL через Docker

Открой PowerShell в папке проекта и выполни:

```powershell
docker-compose up -d
```

Проверь, что контейнер запущен:
```powershell
docker ps
```

Должен быть контейнер `game_profiles_db`.

### 4. Установить PHP зависимости

```powershell
composer install
```

### 5. Создать файл .env

Создай файл `.env` в корне проекта со следующим содержимым:

```env
DB_HOST=127.0.0.1
DB_PORT=5433
DB_NAME=game_profiles
DB_USER=postgres
DB_PASSWORD=postgres

PUSHER_APP_ID=
PUSHER_KEY=
PUSHER_SECRET=
PUSHER_CLUSTER=mt1
```

**Примечание**: Pusher настройки можно оставить пустыми для базового тестирования.

### 6. Запустить сервер

```powershell
php -S localhost:8000 -t public
```

### 7. Открыть в браузере

- Фронтенд: http://localhost:8000/ui.php
- API: http://localhost:8000/profiles

## Альтернатива: использование скрипта start-server.ps1

Для удобного запуска сервера можно использовать скрипт:

```powershell
.\start-server.ps1
```

Скрипт автоматически проверит, запущен ли PostgreSQL контейнер, и запустит PHP сервер.

## Остановка

- **Остановить PHP сервер**: `Ctrl+C` в терминале
- **Остановить PostgreSQL**: `docker-compose down`

## Проверка работоспособности

1. Открой http://localhost:8000/ui.php
2. Создай профиль через форму
3. Обнови статистику профиля
4. Проверь, что изменения сохранились

## Тестирование Real-time синхронизации

Для проверки работы real-time синхронизации через WebSockets:

### Вариант 1: С настройкой Pusher (рекомендуется)

1. **Зарегистрируйся на Pusher**:
   - Перейди на https://dashboard.pusher.com/
   - Создай бесплатное приложение (Channels)
   - Скопируй `Key`, `Cluster`, `App ID`, `Secret`

2. **Настрой `.env` файл**:
   ```env
   PUSHER_APP_ID=твой_app_id
   PUSHER_KEY=твой_key
   PUSHER_SECRET=твой_secret
   PUSHER_CLUSTER=твой_cluster
   ```

3. **Открой страницу тестирования**:
   - http://localhost:8000/realtime-test.html
   - Введи `Pusher Key` и `Cluster`
   - Нажми "Подключиться к Pusher"

4. **В другом окне/вкладке**:
   - Открой http://localhost:8000/ui.php
   - Обнови статистику любого профиля

5. **Вернись на страницу тестирования**:
   - Ты увидишь событие `stats.updated` в реальном времени!

### Вариант 2: Без Pusher (для проверки API)

Если не настроен Pusher, real-time события не будут отправляться, но API будет работать:

1. Обнови статистику через `ui.php` или API
2. Проверь, что данные сохранились в БД
3. Real-time события не будут работать без Pusher

**Примечание**: Без Pusher код будет работать, но события не будут отправляться. Это нормально для базового тестирования функционала CRUD.

## Решение проблем

### Ошибка "Docker не запущен"
- Запусти Docker Desktop
- Подожди, пока он полностью загрузится (иконка в трее станет зелёной)

### Ошибка "PHP не найден"
- Убедись, что PHP добавлен в PATH
- Перезапусти PowerShell после изменения PATH

### Ошибка подключения к БД
- Проверь, что Docker контейнер запущен: `docker ps`
- Если контейнер не запущен: `docker-compose up -d`
- Убедись, что в `.env` указан правильный порт (5433 для Docker контейнера)
- Если используешь локальный PostgreSQL, измени порт в `.env` на 5432 и укажи правильный пароль

### Ошибка при запуске сервера
- Убедись, что порт 8000 свободен
- Попробуй другой порт: `php -S localhost:8080 -t public`

### Ошибка "could not find driver" (PDOException)
Это означает, что в PHP не включено расширение `pdo_pgsql` для работы с PostgreSQL.

**Решение:**
1. Найди файл `php.ini` (выполни `php --ini`)
2. Открой `php.ini` в текстовом редакторе
3. Раскомментируй (убери `;` в начале) строки:
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   ```
4. Сохрани файл и перезапусти сервер

**Проверка расширений:**
```bash
php -m | grep pdo
php -m | grep pgsql
```

Должны быть видны: `pdo`, `pdo_pgsql`, `pgsql`

