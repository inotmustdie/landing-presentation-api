# Landing Presentation API

Backend-сервис для лендинг-презентации разработчика, реализованный на `PHP 8.1` и `Laravel 10`.

Проект закрывает обязательную часть тестового задания:

- REST API для формы обратной связи
- валидация и корректные HTTP-статусы
- отправка уведомлений владельцу сайта и копии пользователю
- AI-интеграция с graceful fallback
- rate limiting на файловом cache-драйвере
- логирование запросов в файл
- файловая статистика обращений
- OpenAPI-документация

## Запуск

Проект можно запускать двумя способами:

- обычный локальный запуск через `PHP/Composer`
- универсальный запуск через `Docker`

## Запуск без Docker

1. Установить зависимости:

```bash
composer install
```

2. Создать `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

3. Запустить локальный сервер:

```bash
php artisan serve
```

4. API будет доступен по адресу:

```text
http://127.0.0.1:8000
```

## Запуск через Docker

Требования:

- `Docker`
- `Docker Compose`

Быстрый старт:

```bash
docker compose up --build
```

После запуска будут доступны:

- API: `http://127.0.0.1:8080`
- Swagger UI: `http://127.0.0.1:8080/docs`
- OpenAPI JSON: `http://127.0.0.1:8080/openapi.json`
- Mailpit UI: `http://127.0.0.1:8025`

Что делает контейнер при старте:

- копирует `.env.example` в `.env`, если `.env` отсутствует
- устанавливает `composer`-зависимости, если папка `vendor` ещё не создана
- генерирует `APP_KEY`, если ключ отсутствует
- подготавливает папки `storage` и `bootstrap/cache`

Остановка контейнеров:

```bash
docker compose down
```

Пересборка после изменений в Docker-конфигурации:

```bash
docker compose up --build --force-recreate
```

## Переменные окружения

Минимально важные настройки:

```dotenv
APP_NAME="InternetLab Landing API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="InternetLab Landing API"
CONTACT_OWNER_EMAIL="owner@example.com"

CONTACT_RATE_LIMIT_MAX_ATTEMPTS=5
CONTACT_RATE_LIMIT_DECAY_SECONDS=600

OPENAI_ENABLED=true
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_TIMEOUT=12
```

Для реальной почтовой отправки можно переключить `MAIL_MAILER` на `smtp` и заполнить SMTP-параметры.

Для Docker-режима по умолчанию используется `Mailpit`, поэтому письма можно смотреть локально через веб-интерфейс без внешнего SMTP.

## Технологии

- Backend: `PHP 8.1`, `Laravel 10`
- HTTP client: `Laravel Http Client / Guzzle`
- Mail: `Laravel Mail`
- AI: `OpenAI-compatible Chat Completions API`
- Хранение данных: файловая система Laravel (`storage/`)
- Тесты: `PHPUnit`

## Архитектура

Использована слоистая структура:

- `app/Http/Controllers/Api` — контроллеры API
- `app/Http/Requests` — валидация входных данных
- `app/Services` — бизнес-логика, AI и orchestration
- `app/Repositories` — файловое хранение метрик
- `app/Mail` — email-уведомления
- `app/Http/Middleware` — аудит запросов

Поток обработки `POST /api/contact`:

1. Запрос проходит валидацию `ContactRequest`
2. Срабатывает rate limiter по IP и email
3. Middleware пишет аудит запроса в файл
4. `ContactService` вызывает AI-анализ
5. При недоступности AI включается эвристический fallback
6. Отправляются 2 письма
7. Обновляется файловая статистика
8. Клиент получает JSON-ответ

## Эндпоинты

### `POST /api/contact`

Принимает:

```json
{
  "name": "Иван Петров",
  "phone": "+79991234567",
  "email": "ivan@example.com",
  "comment": "Добрый день! Хочу обсудить интеграцию AI в форму заявки."
}
```

Успешный ответ:

```json
{
  "message": "Contact request accepted.",
  "data": {
    "contact_id": "6fcb4a1e-8e6c-44e2-b53a-63db96238f66",
    "sentiment": "positive",
    "category": "integration",
    "ai_provider": "openai",
    "fallback_used": false
  }
}
```

### `GET /api/health`

Проверка статуса сервиса, mailer и AI-конфигурации.

### `GET /api/metrics`

Возвращает агрегированную статистику, которая хранится в `storage/app/private/contact-metrics.json`.

### `GET /docs`

Swagger UI для OpenAPI-спецификации.

### `GET /openapi.json`

Сырая OpenAPI-спецификация.

## Примеры curl

```bash
curl -X POST http://127.0.0.1:8000/api/contact \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Иван Петров",
    "phone": "+79991234567",
    "email": "ivan@example.com",
    "comment": "Добрый день! Интересует AI-интеграция и backend-разработка."
  }'
```

```bash
curl http://127.0.0.1:8000/api/health
```

```bash
curl http://127.0.0.1:8000/api/metrics
```

## AI-интеграция

Реализована функция анализа обращения:

- определение тональности комментария
- классификация типа запроса
- генерация краткого автоматического ответа пользователю

### Как это работает

Сервис `AiContactInsightsService` отправляет комментарий в OpenAI-compatible API и ожидает строго JSON-ответ.

Если AI недоступен, сервис:

- не роняет endpoint
- логирует причину fallback
- использует локические эвристики для sentiment/category/reply

### Пример промпта

Сервис просит модель вернуть JSON c полями:

- `sentiment`
- `category`
- `reply`
- `summary`

### Что сделано с помощью AI

Для подготовки решения AI использовался как инженерный помощник:

- декомпозиция структуры Laravel-проекта
- формулировка OpenAPI-спецификации
- ускорение написания boilerplate-кода

Ручные доработки:

- архитектура слоёв
- Laravel-реализация rate limiter
- логика fallback
- обработка ошибок
- тесты и README

## Хранение данных

- Логи запросов: `storage/logs/request-audit.log`
- Основной лог приложения: `storage/logs/laravel.log`
- Лог AI fallback: `storage/logs/ai-fallback.log`
- Метрики: `storage/app/private/contact-metrics.json`
- Rate limiting: файловый cache Laravel (`storage/framework/cache/data`)

## Тесты

```bash
php artisan test
```

Через Docker:

```bash
docker compose exec app php artisan test
```

Покрыты сценарии:

- успешная отправка формы
- ошибка валидации
- AI fallback
- rate limiting

## Примечания по деплою

Проект можно задеплоить на `Render`, `Railway`, `AnyHost` или обычный VPS с `PHP 8.1+`.

Если внешний SMTP недоступен, для демо можно оставить `MAIL_MAILER=log`, и письма будут фиксироваться в логах Laravel без падения сценария.

## Docker-файлы

В проект добавлены:

- `Dockerfile` — PHP 8.1 FPM-образ приложения
- `docker-compose.yml` — сервисы `app`, `nginx`, `mailpit`
- `docker/app/entrypoint.sh` — автоинициализация окружения
- `docker/nginx/default.conf` — конфигурация nginx для Laravel
