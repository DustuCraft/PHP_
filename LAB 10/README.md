# FastDelivery — лабораторная работа №10 (вариант 4)

Симулятор доставки еды: корзина с фастфудом, комбо и напитками, расчёт доставки
курьером или самовывоза. Демонстрирует интерфейсы, абстрактный класс, trait,
пространства имён и собственные исключения.

## Запуск

```powershell
# из папки lab10
php -S 127.0.0.1:8081 -t public
```

Открыть <http://127.0.0.1:8081>.

Проверка синтаксиса и тестов:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
php tests/run.php
```

Требуется PHP 8.2+ (используются `readonly` классы и перечисления).

## Структура

```
lab10/
├── public/
│   ├── index.php              # сценарий: корзина, форма, обработка ошибок
│   └── assets/
│       ├── app.css
│       ├── courier-scooter.png
│       └── courier-walk.png
├── src/
│   ├── bootstrap.php          # автозагрузка по префиксу App\
│   ├── Cart/Cart.php          # корзина в сессии
│   ├── Catalog/               # Product, Menu — предметный каталог
│   ├── Contracts/             # DeliveryCalculator — контракт
│   ├── Delivery/              # AbstractDeliveryCalculator, Courier, Pickup
│   ├── Enums/                 # DeliveryMethod, Category
│   ├── Exceptions/            # AddressException, DeliveryException
│   ├── Services/              # DeliveryService — DI по интерфейсу
│   ├── Support/               # LogsActivity (trait), Money
│   └── ValueObject/           # Order, OrderItem, Address, PickupPoint, DeliveryQuote
├── docs/screens/              # скриншоты страниц для отчёта
└── tests/run.php              # 22 проверки, включая ошибки и finally
```

## Соответствие требованиям работы

| Требование | Где реализовано |
|---|---|
| Интерфейс с двумя типизированными методами | `App\Contracts\DeliveryCalculator`: `method()`, `calculate()` |
| Две взаимозаменяемые реализации | `App\Delivery\Courier`, `App\Delivery\Pickup` |
| Абстрактный класс с общим алгоритмом и точками расширения | `AbstractDeliveryCalculator::calculate()` — `final`, плюс три абстрактных метода |
| Trait с небольшим поведением, минимум в двух классах | `App\Support\LogsActivity` (наследуется обоими калькуляторами) |
| Четыре пространства имён | `Contracts`, `Services`, `Exceptions`, `Delivery`/`Catalog`/`ValueObject` |
| Два своих исключения | `AddressException` (входные данные), `DeliveryException` (сбой операции, с `previous`) |
| DI по интерфейсу | `DeliveryService::__construct(list<DeliveryCalculator>)` |
| `use` и псевдоним `as` | псевдоним `OrderItem as CartLine` в `tests/run.php` |
| Восемь и более тестов | `tests/run.php` — 22 проверки, включая `finally` и `previous` |
| Отчёт | см. раздел «Материалы для отчёта» |

## Бизнес-правила симуляции

- Курьер: 600 ₸ + 120 ₸ за каждый км дальше 3 км от кухни; бесплатно от 8 000 ₸.
- Районы: Центр 2 км, Бостандык 5,2 км, Ауэзов 7,4 км, Медеу 9 км, Алатау 11,5 км.
- Срок курьера: 20 мин + 3 мин на км + 2 мин на позицию.
- Самовывоз: 0 ₸, 15 мин + 2 мин на позицию.
- Сбой операции: закрытый пункт `PVZ-04`, заказ больше 12 позиций.
- Ошибки ввода: пустой адрес, неверный номер дома или улица, неизвестный пункт.

## Материалы для отчёта

- Схема каталогов — раздел «Структура».
- Связи типов: `DeliveryCalculator` ← `AbstractDeliveryCalculator` ← `Courier`/`Pickup`;
  trait `LogsActivity` вставлен в абстрактный класс; `DeliveryService` зависит от интерфейса.
- Иерархия исключений: `AddressException extends InvalidArgumentException`,
  `DeliveryException extends RuntimeException` (хранит `method` и `previous`).
- Тесты: вывод `php tests/run.php` (12 сценариев, 22 проверки).
- Снимки экрана: `docs/screens/courier.png`, `docs/screens/pickup.png`, `docs/screens/error.png`.

## Скриншоты

| Файл | Что показывает |
|---|---|
| `docs/screens/courier.png` | Корзина из трёх категорий, доставка курьером, 32–42 мин, журнал trait |
| `docs/screens/pickup.png` | Самовывоз, пункт «Самал», бесплатная доставка |
| `docs/screens/error.png` | Ошибка `AddressException` для дома «abc» |

Проверено на PHP 8.2.12 (XAMPP). Для лабораторной достаточно 8.2+; если преподаватель
требует 8.4 — код совместим без изменений.
