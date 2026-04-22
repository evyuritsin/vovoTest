# Тестовое задание

## Описание

Docker окружение Laravel 13 с MySQL 8.0 и полнотекстовым поиском по товарам.

## Быстрый старт

### 1. Запуск контейнеров

```bash
docker-compose up -d --build
```

### 2. Установка зависимостей

```bash
docker exec --user $UID:$UID laravel_php composer install
```

### 3. Миграции и сидеры

```bash
# Миграции
docker exec --user $UID:$UID laravel_php php artisan migrate

# Сидеры (50 товаров + 10 категорий)
docker exec --user $UID:$UID laravel_php php artisan db:seed
```

### 4. Проверка работы

Откройте в браузере: http://localhost:8080/api/products

## API Endpoints

### GET /api/products

Поиск товаров с фильтрами и сортировкой.

**Фильтры:**

| Параметр | Описание | Пример |
|----------|---------|--------|
| `q` | Поиск по названию (FULLTEXT) | `?q=iPhone` |
| `price_from` | Цена от | `?price_from=100` |
| `price_to` | Цена до | `?price_to=500` |
| `category_id` | ID категории | `?category_id=1` |
| `in_stock` | В наличии (true/false) | `?in_stock=true` |
| `rating_from` | Рейтинг от | `?rating_from=4.0` |

**Сортировка:**

| Параметр | Описание |
|----------|---------|
| `sort=price_asc` | По цене ↑ |
| `sort=price_desc` | По цене ↓ |
| `sort=rating_desc` | По рейтингу ↓ |
| `sort=newest` | Сначала новые |

**Пагинация:**

| Параметр | Описание | По умолчанию |
|----------|---------|--------------|
| `page` | Номер страницы | 1 |
| `per_page` | Элементов на страницу | 15 |

### Примеры запросов

```bash
# Все товары
curl http://localhost:8080/api/products

# Поиск iPhone
curl "http://localhost:8080/api/products?q=iPhone"

# Фильтр по цене и сортировка
curl "http://localhost:8080/api/products?price_from=100&price_to=500&sort=price_asc"

# Комбинированный фильтр
curl "http://localhost:8080/api/products?q=iPhone&in_stock=true&rating_from=4.0&sort=price_desc"
```

## Тесты

```bash
# Запуск всех тестов
docker exec --user $UID:$UID laravel_php php artisan test

# Только тесты Product API
docker exec --user $UID:$UID laravel_php php artisan test --filter=ProductApiTest
```

**Результат:** 13 тестов, 151 подтверждение

## Структура базы данных

### Таблица categories

| Поле | Тип | Описание |
|------|-----|---------|
| id | bigint | PK |
| name | varchar | Название категории |
| created_at | timestamp | |
| updated_at | timestamp | |

### Таблица products

| Поле | Тип | Описание |
|------|-----|---------|
| id | bigint | PK |
| name | varchar | Название товара (FULLTEXT индекс) |
| price | decimal(10,2) | Цена |
| category_id | bigint | FK → categories.id |
| in_stock | boolean | В наличии |
| rating | float | Рейтинг (0-5) |
| created_at | timestamp | |
| updated_at | timestamp | |

**Индексы:**
- `name` — FULLTEXT (MySQL) / B-tree (SQLite)
- `price` — B-tree
- `rating` — B-tree
- `category_id` — B-tree (FK)

## Стек

- PHP 8.3-FPM
- Laravel 13
- MySQL 8.0
- Nginx (Alpine)
- Redis (Alpine)