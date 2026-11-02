ЛАБОРАТОРНАЯ РАБОТА №4
Разработка набора типизированных пользовательских функций
Вариант 20 — «Конвертация валют»
Дисциплина: Программирование на PHP
PHP 8.1+ (проверено на PHP 8.3.36), declare(strict_types=1)

1. НАЗНАЧЕНИЕ ПРОЕКТА
---------------------
Обменный пункт: проверка суммы и курса, расчёт комиссии, конвертация валюты,
классификация операции, отбор крупных операций и формирование квитанции.
Все расчёты выполняются чистыми типизированными функциями из functions.php;
index.php только читает ввод, вызывает функции и выводит HTML.
Глобальные переменные не используются, зависимости передаются параметрами.

2. СТРУКТУРА
------------
lab-04/
  functions.php      библиотека типизированных функций (без HTML-вывода)
  index.php          страница-калькулятор: форма, результат, портфель операций
  tests.php          23 теста: CLI-отчёт и HTML-таблица результатов
  README.txt         этот файл
  assets/style.css   оформление страниц
  screenshots/       сюда складываются скриншоты для отчёта

3. ЗАПУСК В XAMPP
-----------------
1) Скопируйте папку lab-04 в C:\xampp\htdocs\  (получится C:\xampp\htdocs\lab-04).
2) Запустите в панели XAMPP модуль Apache.
3) Откройте в браузере:
      http://localhost/lab-04/          — калькулятор обмена
      http://localhost/lab-04/tests.php — отчёт о тестах
4) Проверка синтаксиса (командная строка):
      C:\xampp\php\php.exe -l C:\xampp\htdocs\lab-04\functions.php
      C:\xampp\php\php.exe -l C:\xampp\htdocs\lab-04\index.php
      C:\xampp\php\php.exe -l C:\xampp\htdocs\lab-04\tests.php
5) Тесты в терминале:
      C:\xampp\php\php.exe C:\xampp\htdocs\lab-04\tests.php
   Код возврата 0 — все тесты пройдены, 1 — есть ошибки.

Альтернатива без Apache (встроенный сервер PHP):
      C:\xampp\php\php.exe -S localhost:8000 -t C:\xampp\htdocs\lab-04
      http://localhost:8000/

4. КОНТРАКТЫ ФУНКЦИЙ
--------------------
validateAmount(float $amount, string $field = 'Сумма'): void
    Сумма конечна, больше 0 и не больше 1 000 000 000.
    Исключение: InvalidArgumentException.

validateRate(float $rate, string $pair = 'Курс'): void
    Курс конечен, больше 0 и не больше 100 000.
    Исключение: InvalidArgumentException.

validateCommission(float $percent): void
    Комиссия в диапазоне [0; 10] %.
    Исключение: InvalidArgumentException.

commissionFee(float $convertedAmount, float $percent, float $minFee = 0.0): float
    Комиссия = max(сумма × процент / 100; минимум), но не больше суммы.
    Параметр по умолчанию: $minFee = 0.0.
    Исключение: InvalidArgumentException.

conversionDetails(float $amount, float $rate, float $commissionPercent = 0.0,
                  float $minFee = 0.0): array
    Возвращает amount, rate, gross, fee, net, category.
    Исключение: InvalidArgumentException.

convert(float $amount, float $rate, float $commissionPercent = 0.0,
        float $minFee = 0.0): float
    Сумма к зачислению = round(amount × rate − комиссия, 2).
    Исключение: InvalidArgumentException.

resolveRate(string $from, string $to, array $rates): float
    Прямой курс из справочника, иначе обратный (1 / курс), иначе кросс-курс
    через тенге: (FROM/KZT) / (TO/KZT). Одинаковые валюты дают 1.0.
    Исключения: InvalidArgumentException (формат кода), DomainException (нет пары).

classifyConversion(float $amountKzt): string
    «крупная» ≥ 1 000 000; «средняя» ≥ 100 000;
    «небольшая» ≥ 10 000; иначе «мелкая».
    Исключение: InvalidArgumentException.

classifyAmountInKzt(float $amount, string $currency, array $rates): string
    Пересчитывает сумму любой валюты в тенге и относит её к категории —
    нужно для обратного направления (KZT → USD, EUR → USD и т. п.).
    Исключения: InvalidArgumentException, DomainException.

formatMoney(float|int $amount, string $currency, ?int $decimals = null): string
    Union-тип float|int и nullable-параметр $decimals.
    По умолчанию 2 знака, для JPY/KRW/VND — 0; разделитель тысяч — пробел.
    Исключение: InvalidArgumentException.

convertOperation(array $operation, array $rates): array
    Преобразует запись client/amount/currency/commission/min_fee в полный расчёт.
    Исключение: InvalidArgumentException (нет обязательного поля).

filterOperations(array $operations, float $minAmount = 0.0): array
    Стрелочная callback-функция оставляет операции с net ≥ порога.
    Исключение: InvalidArgumentException.

summarize(array $items, callable $selector): array
    Дополнительное задание: count, min, max, sum, avg, median по числовому показателю.
    Исключение: InvalidArgumentException (пустой набор, нечисло).

buildReceipt(string $client, float $amount, string $currency, float $rate,
             float $fee, ?string $comment = null, string $targetCurrency = 'KZT',
             ?string $category = null): string
    Текст квитанции; nullable-комментарий может отсутствовать, валюта получения
    задаётся отдельно, категория может быть передана извне.
    Исключение: InvalidArgumentException (пустое имя, комиссия больше суммы и др.).

sumAmounts(float ...$amounts): float
    Variadic-параметр: сумма пакета платежей.
    Исключение: InvalidArgumentException (пустой пакет, неверная сумма).

5. ЧТО ДЕМОНСТРИРУЕТ РАБОТА
---------------------------
- declare(strict_types=1) во всех файлах;
- 14 именованных функций с типами параметров и результата;
- nullable-тип ?string и ?int, union-тип float|int;
- параметры по умолчанию ($minFee, $commissionPercent, $decimals) и variadic sumAmounts(...);
- два вида исключений: InvalidArgumentException и DomainException,
  плюс естественный TypeError при нарушении strict_types;
- замыкание с use: $quote захватывает курс и условия обмена;
- стрелочные функции как callback для array_map, array_filter (внутри
  filterOperations) и usort;
- отсутствие global; справочник курсов передаётся в resolveRate/convertOperation;
- вычисления отделены от HTML: functions.php ничего не печатает;
- обмен работает в обе стороны: прямой, обратный и кросс-курс через тенге;
- 27 тестов: обычные, граничные, ошибочные, nullable, strict_types,
  обратное направление, замыкание, callback, summarize с двумя селекторами.

6. КУРСЫ
--------
В index.php задан демонстрационный справочник:
    USD/KZT = 470.00, EUR/KZT = 512.00,
    RUB/KZT = 5.85,  CNY/KZT = 65.40.
Из него выводятся обратные курсы (KZT/USD = 1/470) и кросс-курсы
(USD/EUR = 470/512), поэтому обмен доступен между всеми четырьмя валютами
в обоих направлениях.
Курсы не запрашиваются из интернета: проект работает автономно и
воспроизводимо на защите. Чтобы подключить внешний источник, достаточно
заменить массив $rates результатом запроса — остальные функции не меняются.

7. ТЕСТЫ
---------
tests.php печатает таблицу «сценарий — вход — ожидалось — получено — статус».
В браузере тот же набор выводится HTML-таблицей (удобно для скриншота).
Итог: 27 из 27 тестов пройдены, код возврата 0.

8. ПРИМЕЧАНИЕ ОБ API-КЛЮЧЕ
--------------------------
Ключ из задания не использован: он не подошёл ни к одному из популярных
сервисов курсов валют (currencyapi.com, freecurrencyapi.com,
exchangerate.host, fastforex.io, exchangerate-api.com — ответ «invalid key»).
Проект намеренно не зависит от сети. Если преподаватель выдал конкретный
сервис курсов, пришлите его название/URL — подключу запрос и кэширование.
