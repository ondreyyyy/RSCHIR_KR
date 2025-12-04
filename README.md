# Система хранения игровых профилей

Современная backend-система для управления игровыми профилями с поддержкой real-time обновлений и интеграцией со Steam API. Проект реализован на PHP с использованием принципов Clean Architecture.

## О проекте

Это система для хранения и управления игровыми профилями, которая позволяет:
- Создавать, обновлять и удалять профили игроков
- Хранить игровую статистику (уровень, опыт, победы, поражения)
- Импортировать профили из Steam API
- Получать обновления статистики в реальном времени через WebSocket
- Защиту от SQL-инъекций и XSS-атак

Проект разработан как курсовая работа с применением современных подходов к архитектуре программного обеспечения.

## Архитектура

Проект следует принципам **Clean Architecture** (Чистая Архитектура), что обеспечивает:

- **Разделение ответственности** - каждый слой решает свои задачи
- **Независимость от фреймворков** - бизнес-логика не зависит от конкретных технологий
- **Тестируемость** - легко писать unit-тесты благодаря изоляции слоев
- **Расширяемость** - легко добавлять новые функции без изменения существующего кода

### Структура проекта

```
RSCHIR_KR/
├── app/
│   ├── Application/          # Слой приложения
│   │   ├── Ports/            # Интерфейсы для внешних сервисов
│   │   └── UseCase/          # Бизнес-логика (Use Cases)
│   ├── Domain/               # Доменный слой (бизнес-логика)
│   │   ├── Profile.php       # Сущность профиля
│   │   ├── Stats.php         # Value Object для статистики
│   │   └── ProfileRepositoryInterface.php
│   └── Infrastructure/       # Инфраструктурный слой
│       ├── Broadcast/        # Реализация WebSocket (Pusher)
│       ├── Persistence/      # Реализация репозитория (PostgreSQL)
│       ├── Security/         # Валидация и санитизация
│       └── Steam/            # Клиент для Steam API
├── config/                   # Конфигурационные файлы
├── database/                 # SQL схемы
├── public/                   # Точка входа (API и UI)
├── tests/                    # Тесты
│   ├── Unit/                 # Unit-тесты
│   └── Integration/          # Интеграционные тесты
└── composer.json
```

### Слои архитектуры

#### 1. **Domain Layer** (Доменный слой)
Содержит бизнес-логику и сущности:
- `Profile` - основная сущность профиля игрока
- `Stats` - Value Object для игровой статистики
- `ProfileRepositoryInterface` - интерфейс репозитория (порт)

**Принцип**: Доменный слой не знает о базе данных, HTTP, WebSocket и т.д.

#### 2. **Application Layer** (Слой приложения)
Содержит Use Cases (сценарии использования):
- `CreateProfileUseCase` - создание профиля
- `GetProfileUseCase` - получение профиля
- `UpdateStatsAndBroadcastUseCase` - обновление статистики с broadcast
- `DeleteProfileUseCase` - удаление профиля
- `ListProfilesUseCase` - список профилей
- `ImportProfileFromSteamUseCase` - импорт из Steam

**Принцип**: Use Cases координируют работу доменных сущностей и репозиториев.

#### 3. **Infrastructure Layer** (Инфраструктурный слой)
Реализации интерфейсов:
- `PdoProfileRepository` - PostgreSQL реализация репозитория
- `PusherBroadcaster` - реализация WebSocket через Pusher
- `SteamHttpClient` - клиент для Steam API
- `InputValidator` - валидация и защита от XSS

**Принцип**: Инфраструктура зависит от домена, а не наоборот.

### Поток данных

```
HTTP Request → index.php → UseCase → Domain → Repository → Database
                                    ↓
                              Broadcaster → Pusher → WebSocket Clients
```

## Безопасность

### Защита от SQL-инъекций
- Все SQL-запросы используют **prepared statements** через PDO
- Параметры привязываются с указанием типов (`PDO::PARAM_INT`, `PDO::PARAM_STR`)
- Никакие пользовательские данные не подставляются напрямую в SQL

### Защита от XSS-атак
- Все строковые входные данные проходят через `InputValidator`
- Используется `htmlspecialchars()` с флагами `ENT_QUOTES | ENT_SUBSTITUTE`
- HTML-теги удаляются через `strip_tags()`
- Валидация формата данных (SteamID, external ID и т.д.)

### Валидация входных данных
- Проверка типов данных
- Ограничение длины строк
- Валидация формата (SteamID64, API ключи)
- Нормализация числовых значений

## Установка и запуск

### Требования
- PHP >= 8.1
- Docker Desktop
- Composer
- Pusher аккаунт для real-time функционала

### Шаги установки

1. **Клонируйте репозиторий** (или распакуйте проект)

2. **Установите зависимости:**
```bash
composer install
```

3. **Настройте базу данных:**
   
   Создайте файл `.env` в корне проекта:
```env
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=game_profiles
DB_USER=postgres
DB_PASSWORD=postgres

# Для real-time функционала
PUSHER_KEY=your_pusher_key
PUSHER_SECRET=your_pusher_secret
PUSHER_APP_ID=your_pusher_app_id
PUSHER_CLUSTER=mt1
```

4. **Запустите PostgreSQL через Docker:**
```bash
docker-compose up -d
```

5. **Запустите сервер:**
```bash
# Windows (PowerShell)
.\start-server.ps1

# Linux/Mac
php -S localhost:8000 -t public
```

6. **Откройте в браузере:**
   - API: http://localhost:8000
   - UI: http://localhost:8000/ui.php
   - Real-time тест: http://localhost:8000/realtime-test.html

## API Endpoints

### GET `/profiles`
Получить список профилей с пагинацией.

**Параметры:**
- `limit` (int, опционально, по умолчанию 50) - количество записей
- `offset` (int, опционально, по умолчанию 0) - смещение

**Пример:**
```bash
curl http://localhost:8000/profiles?limit=10&offset=0
```

**Ответ:**
```json
[
  {
    "id": 1,
    "external_id": "steam_76561198012345678",
    "nickname": "PlayerName",
    "stats": {
      "level": 10,
      "experience": 5000,
      "wins": 25,
      "losses": 15
    }
  }
]
```

### GET `/profiles/{id}`
Получить профиль по ID.

**Пример:**
```bash
curl http://localhost:8000/profiles/1
```

### POST `/profiles`
Создать новый профиль.

**Тело запроса:**
```json
{
  "external_id": "steam_76561198012345678",
  "nickname": "PlayerName",
  "stats": {
    "level": 1,
    "experience": 0,
    "wins": 0,
    "losses": 0
  }
}
```

**Пример:**
```bash
curl -X POST http://localhost:8000/profiles \
  -H "Content-Type: application/json" \
  -d '{"external_id":"steam_123","nickname":"TestPlayer","stats":{"level":1,"experience":0,"wins":0,"losses":0}}'
```

### DELETE `/profiles/{id}`
Удалить профиль по ID.

**Пример:**
```bash
curl -X DELETE http://localhost:8000/profiles/1
```

### POST `/stats/update`
Обновить статистику профиля и отправить broadcast событие.

**Тело запроса:**
```json
{
  "id": 1,
  "stats": {
    "level": 20,
    "experience": 10000,
    "wins": 50,
    "losses": 30
  }
}
```

**Пример:**
```bash
curl -X POST http://localhost:8000/stats/update \
  -H "Content-Type: application/json" \
  -d '{"id":1,"stats":{"level":20,"experience":10000,"wins":50,"losses":30}}'
```

### POST `/import/from-steam`
Импортировать профиль из Steam API.

**Тело запроса:**
```json
{
  "key": "your_steam_api_key",
  "steam_id": "76561198012345678"
}
```

**Пример:**
```bash
curl -X POST http://localhost:8000/import/from-steam \
  -H "Content-Type: application/json" \
  -d '{"key":"YOUR_STEAM_API_KEY","steam_id":"76561198012345678"}'
```

### GET `/pusher-config`
Получить конфигурацию Pusher для клиентской части.

## Тестирование

Проект включает полное покрытие тестами backend функционала.

### Запуск тестов

```bash
# Все тесты
vendor/bin/phpunit

# Только unit-тесты
vendor/bin/phpunit tests/Unit

# Только интеграционные тесты
vendor/bin/phpunit tests/Integration

# С покрытием кода (если установлен Xdebug)
vendor/bin/phpunit --coverage-html coverage
```

### Структура тестов

- **Unit тесты** (`tests/Unit/`):
  - Тесты UseCase классов
  - Тесты валидации и безопасности
  - Моки зависимостей

- **Integration тесты** (`tests/Integration/`):
  - Тесты репозитория с реальной БД
  - Тесты защиты от SQL-инъекций

## Принципы работы

### Dependency Inversion (Инверсия зависимостей)
Высокоуровневые модули (UseCase) не зависят от низкоуровневых (Repository). Оба зависят от абстракций (интерфейсов).

### Single Responsibility (Единственная ответственность)
Каждый класс решает одну задачу:
- `CreateProfileUseCase` - только создание профиля
- `PdoProfileRepository` - только работа с БД
- `InputValidator` - только валидация

### Open/Closed Principle (Открыт для расширения, закрыт для модификации)
Можно добавить новую реализацию репозитория (например, для MongoDB) без изменения UseCase.

### Interface Segregation (Разделение интерфейсов)
Интерфейсы маленькие и специфичные:
- `BroadcasterInterface` - только broadcast
- `SteamClientInterface` - только работа со Steam

## Технологии

- **PHP 8.1+** - основной язык
- **PostgreSQL** - база данных
- **PDO** - работа с БД
- **Pusher** - WebSocket для real-time
- **PHPUnit** - тестирование
- **Docker** - контейнеризация БД
- **Composer** - управление зависимостями

## Идея проекта

Проект демонстрирует:
1. **Clean Architecture** - разделение на слои с четкими границами
2. **SOLID принципы** - правильное проектирование классов
3. **Безопасность** - защита от распространенных атак
4. **Тестируемость** - покрытие тестами критичного функционала
5. **Расширяемость** - легко добавлять новые функции

## Для чего это полезно

- Изучение Clean Architecture на практике
- Понимание разделения ответственности между слоями
- Пример безопасной работы с БД и валидации данных
- Демонстрация real-time функционала
- Интеграция с внешними API (Steam)

## Дополнительная информация

### Получение Steam API ключа
1. Перейдите на https://steamcommunity.com/dev/apikey
2. Зарегистрируйтесь и получите ключ
3. Добавьте ключ в `.env` или используйте в запросах

### Получение SteamID64
1. Используйте https://steamid.io/
2. Введите URL вашего Steam профиля
3. Скопируйте SteamID64 (начинается с 7656119...)

### Настройка Pusher
1. Зарегистрируйтесь на https://dashboard.pusher.com/
2. Создайте приложение
3. Скопируйте ключи в `.env`