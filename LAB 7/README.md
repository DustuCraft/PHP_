# Лабораторная работа 7 — Вариант 4: Система общежития

Роли: `resident` (жилец) и `manager` (менеджер).
Защищённое действие: жилец создаёт заявку, менеджер меняет её статус.
Cookie-настройка: предпочитаемый язык (`lang`: ru / kk / en, allow-list).

## Запуск
    php -S localhost:8000 -t public
Откройте http://localhost:8000/login.php (нужно расширение mbstring; каталог `storage/` должен быть доступен на запись).

## Учебные учётные записи
| Роль     | E-mail              | Пароль        |
|----------|---------------------|---------------|
| resident | resident@atu.edu.kz | Resident123!  |
| manager  | manager@atu.edu.kz  | Manager123!   |

## Автотесты (T1–T12 + сценарии варианта)
    bash tests/run_tests.sh

## Структура
- `src/session.php` — настройка cookie, session_start(), вход/выход, requireLogin/requireRole, CSRF
- `src/i18n.php` — cookie `lang` и переводы
- `src/data.php` — учебные пользователи (только хеши) и хранилище заявок (JSON + flock)
- `public/login.php`, `dashboard.php`, `manager.php`, `logout.php`
