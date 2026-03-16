# Система управления конференц-залами (Business Park Management System)

MVP клиент-серверного приложения для организации управления конференц-залами в бизнес-парках. Проект разработан в рамках практической работы по дисциплине «Создание клиент-серверных приложений» (Университет ИТМО).

## 🚀 Технологический стек

*   **Backend:** PHP 8.3 + Laravel 12
*   **Frontend:** Angular 19 + Angular Material
*   **Database:** PostgreSQL 15
*   **Infrastructure:** Docker, Docker Compose
*   **Auth:** Laravel Sanctum (Token based)
*   **Architecture:** REST API

---

## 🛠 Установка и запуск

### 1. Требования
*   Установленный Docker и Docker Compose.

### 2. Запуск контейнеров
Соберите и запустите проект (автоматически установятся зависимости, настроится Nginx и PHP):
```bash
docker-compose up -d --build
```

### 3. Настройка базы данных
Создайте таблицы и наполните их тестовыми данными (включая менеджера для входа):
```bash
docker exec -it bp_app php artisan migrate:fresh --seed
```

### 4. Данные для входа (Менеджер)
Узнать email первого сгенерированного менеджера:
```bash
docker exec -it bp_app php artisan tinker --execute="echo App\Models\Manager::first()->email"
```
**Пароль по умолчанию для всех:** `password`

---

## 📡 API Документация

**Базовый URL:** `http://localhost/api`  
**Заголовки для защищенных роутов:**  
`Authorization: Bearer {token}`  
`Accept: application/json`

### 1. Публичные маршруты (Клиенты / Общее)

| Метод | Маршрут | Описание |
| :--- | :--- | :--- |
| `GET` | `/test-db` | Проверка связей БД (Debug) |
| `GET` | `/v1/halls/filters` | Данные для фильтров (парки, оборудование) |
| `GET` | `/v1/halls` | Поиск залов с фильтрацией (см. ниже) |
| `GET` | `/v1/halls/{id}` | Детальная информация о зале |
| `POST` | `/v1/bookings` | Создание бронирования |
| `POST` | `/v1/login` | Вход для менеджера (получение токена) |

---

### 🔍 Фильтрация залов (`GET /api/v1/halls`)

При поиске залов можно использовать следующие параметры в строке запроса:

| Параметр | Тип | Описание |
| :--- | :--- | :--- |
| `business_park_id` | `uuid` | Фильтр по одному бизнес-парку |
| `business_park_ids[]`| `array` | Фильтр по нескольким бизнес-паркам |
| `capacity_from` | `int` | Минимальная вместимость |
| `capacity_to` | `int` | Максимальная вместимость |
| `city` | `string` | Поиск по городу (нечувствителен к регистру) |
| `equipment` | `csv/array` | Список ID оборудования (через запятую или массивом) |
| `min_price` | `decimal` | Минимальная цена за час |
| `max_price` | `decimal` | Максимальная цена за час |
| `date_range[start]` | `ISO8601` | Начало периода (проверка доступности) |
| `date_range[end]` | `ISO8601` | Конец периода (проверка доступности) |

**Пример запроса:**
`GET /api/v1/halls?capacity_from=20&city=Langfort&date_range[start]=2026-03-15T10:00&date_range[end]=2026-03-15T12:00`

---

### 2. Кабинет Менеджера (Защищено Sanctum)

Все маршруты требуют токен менеджера. Менеджеры видят данные **только своего** бизнес-парка.

#### Управление бронированиями
| Метод | Маршрут | Описание |
| :--- | :--- | :--- |
| `GET` | `/v1/manager/bookings` | Список бронирований парка |
| `POST` | `/v1/manager/bookings/{id}/confirm` | Подтвердить оплату |
| `POST` | `/v1/manager/bookings/{id}/cancel` | Отменить бронирование |
| `POST` | `/v1/manager/logout` | Выход (аннулирование токена) |

#### Управление залами (CRUD)
| Метод | Маршрут | Описание |
| :--- | :--- | :--- |
| `GET` | `/v1/manager/halls` | Список своих залов |
| `POST` | `/v1/manager/halls` | Создать новый зал |
| `PUT` | `/v1/manager/halls/{id}` | Редактировать данные зала |
| `DELETE` | `/v1/manager/halls/{id}` | Деактивировать зал (status -> unavailable) |

#### Аналитика
| Метод | Маршрут | Описание |
| :--- | :--- | :--- |
| `GET` | `/v1/manager/reports/income` | Отчет по доходам (параметры: `date_from`, `date_to`) |

---

## 🏗 Структура данных (UUID)

В проекте используются UUID (Universally Unique Identifiers) для всех первичных ключей. Это обеспечивает:
1. Безопасность (невозможно перебором узнать ID записей).
2. Независимость данных при миграциях между серверами.
3. Готовность к распределенным системам.

---

## 🖥 Фронтенд

Фронтенд — SPA на Angular 19, запускается отдельно от бэкенда.

### Технологии
*   **Angular 19** (standalone components)
*   **Angular Material** — UI-компоненты
*   **RxJS** — реактивные запросы
*   **Nginx** — production-сборка в Docker-контейнере

### Структура (`frontend/src/app/`)

*   `core/services/` — сервисы: `AuthService`, `HallsService`, `BookingsService`, `ApiService`
*   `core/interceptors/` — `AuthInterceptor` (добавляет Bearer-токен к запросам)
*   `core/guards/` — `AuthGuard` (защита маршрутов менеджера)
*   `features/halls/` — публичный каталог и детальная страница залов
*   `features/booking/` — форма бронирования и страница успеха
*   `features/manager/` — кабинет менеджера: бронирования, залы, отчёты, логин
*   `layout/` — шаблоны разметки: публичный сайт и панель менеджера

### Запуск в development-режиме

```bash
cd frontend
npm install
npm start
```

Приложение будет доступно на `http://localhost:4200`. Запросы к `/api` проксируются на бэкенд (`http://localhost`) через `proxy.conf.json`.

### Сборка для production

```bash
cd frontend
npm run build
```

Собранные файлы попадают в `frontend/dist/` и раздаются через Nginx внутри Docker-контейнера.

---

## 📂 Структура проекта (Backend)

*   `app/Filters` — логика фильтрации залов (QueryFilter паттерн).
*   `app/Models` — модели Eloquent со связями и UUID трейтами.
*   `app/Http/Controllers/Api` — контроллеры API (MVP логика).
*   `database/factories` — генераторы тестовых данных.
*   `docker/` — конфигурации Nginx, PHP и Supervisor.

---

### 📄 Лицензия
Разработано для учебных целей Университета ИТМО.