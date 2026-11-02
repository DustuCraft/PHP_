<?php

declare(strict_types=1);

/**
 * Лабораторная работа №4. Вариант 20 — «Конвертация валют».
 * Тесты библиотеки функций: обычные, граничные и ошибочные сценарии.
 *
 * Запуск в терминале:  php tests.php
 * Просмотр в браузере: http://localhost/lab-04/tests.php
 */

require_once __DIR__ . '/functions.php';

/**
 * Представляет значение для отчёта.
 *
 * @param mixed $value Любое значение.
 * @return string Короткое текстовое представление.
 */
function formatValue(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_float($value)) {
        return rtrim(rtrim(number_format($value, 4, '.', ' '), '0'), '.');
    }
    if (is_array($value)) {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);

        return $json === false ? '[...]' : $json;
    }
    if ($value === null) {
        return 'null';
    }

    return (string) $value;
}

/**
 * Экранирует текст для HTML-отчёта.
 *
 * @param string $value Текст.
 * @return string Экранированный текст.
 */
function testEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Минимальный набор тестов: собирает результаты и печатает отчёт.
 */
final class TestRunner
{
    /** @var array<int, array{name: string, input: string, expected: string, actual: string, ok: bool}> */
    private array $rows = [];

    /**
     * Проверяет, что вызов вернул ожидаемое значение.
     *
     * @param string   $name     Название сценария.
     * @param string   $input    Описание входных данных.
     * @param callable $action   Проверяемый вызов.
     * @param mixed    $expected Ожидаемый результат.
     */
    public function value(string $name, string $input, callable $action, mixed $expected): void
    {
        try {
            $actual = $action();
            $ok = $this->equals($actual, $expected);
            $this->rows[] = [
                'name' => $name,
                'input' => $input,
                'expected' => formatValue($expected),
                'actual' => formatValue($actual),
                'ok' => $ok,
            ];
        } catch (Throwable $exception) {
            $this->rows[] = [
                'name' => $name,
                'input' => $input,
                'expected' => formatValue($expected),
                'actual' => 'исключение ' . $exception::class . ': ' . $exception->getMessage(),
                'ok' => false,
            ];
        }
    }

    /**
     * Проверяет, что вызов выбрасывает ожидаемое исключение.
     *
     * @param string   $name        Название сценария.
     * @param string   $input       Описание входных данных.
     * @param callable $action      Проверяемый вызов.
     * @param class-string $class   Ожидаемый класс исключения.
     * @param string   $messagePart Фрагмент текста сообщения (необязательно).
     */
    public function exception(
        string $name,
        string $input,
        callable $action,
        string $class,
        string $messagePart = ''
    ): void {
        $expected = $class . ($messagePart === '' ? '' : ' («' . $messagePart . '»)');

        try {
            $action();
            $this->rows[] = [
                'name' => $name,
                'input' => $input,
                'expected' => $expected,
                'actual' => 'исключение не выброшено',
                'ok' => false,
            ];
        } catch (Throwable $exception) {
            $ok = $exception instanceof $class
                && ($messagePart === '' || str_contains($exception->getMessage(), $messagePart));
            $this->rows[] = [
                'name' => $name,
                'input' => $input,
                'expected' => $expected,
                'actual' => $exception::class . ': ' . $exception->getMessage(),
                'ok' => $ok,
            ];
        }
    }

    /**
     * Сравнивает фактические и ожидаемые значения.
     *
     * @param mixed $actual   Фактическое значение.
     * @param mixed $expected Ожидаемое значение.
     * @return bool Признак совпадения (для float — с точностью до копеек).
     */
    private function equals(mixed $actual, mixed $expected): bool
    {
        if (is_float($expected) && (is_float($actual) || is_int($actual))) {
            return abs((float) $actual - $expected) < 0.005;
        }

        return $actual === $expected;
    }

    /** @return array<int, array{name: string, input: string, expected: string, actual: string, ok: bool}> */
    public function rows(): array
    {
        return $this->rows;
    }

    public function passed(): int
    {
        return count(array_filter($this->rows, static fn(array $row): bool => $row['ok']));
    }

    public function failed(): int
    {
        return count($this->rows) - $this->passed();
    }
}

$rates = [
    'USD/KZT' => 470.00,
    'EUR/KZT' => 512.00,
    'RUB/KZT' => 5.85,
    'CNY/KZT' => 65.40,
];

$tests = new TestRunner();

/* 1. Обычный корректный расчёт. */
$tests->value(
    'Обычная конвертация без комиссии',
    'convert(100, 470)',
    static fn(): float => convert(100.0, 470.0),
    47000.0
);

/* 2. Комиссия по проценту. */
$tests->value(
    'Конвертация с комиссией 1,5 %',
    'convert(100, 470, 1.5)',
    static fn(): float => convert(100.0, 470.0, 1.5),
    46295.0
);

/* 3. Минимальная комиссия важнее процента. */
$tests->value(
    'Срабатывает минимальная комиссия',
    'convert(10, 470, 1.5, 200)',
    static fn(): float => convert(10.0, 470.0, 1.5, 200.0),
    4500.0
);

/* 3a. Обратный курс: тенге → доллары. */
$tests->value(
    'Обратное направление KZT → USD',
    'convert(470 000, 1/470)',
    static fn(): float => convert(470_000.0, resolveRate('KZT', 'USD', $rates)),
    1000.0
);

/* 3b. Кросс-курс через тенге: USD → EUR. */
$tests->value(
    'Кросс-курс USD → EUR',
    'convert(100, 470/512)',
    static fn(): float => convert(100.0, resolveRate('USD', 'EUR', $rates)),
    91.8
);

/* 4. Нижняя допустимая граница. */
$tests->value(
    'Нижняя граница суммы 0,01',
    'validateAmount(0.01)',
    static function (): string {
        validateAmount(0.01);

        return 'принято';
    },
    'принято'
);

/* 5. Верхняя допустимая граница. */
$tests->value(
    'Верхняя граница суммы 1 000 000 000',
    'validateAmount(1 000 000 000)',
    static function (): string {
        validateAmount(1_000_000_000.0);

        return 'принято';
    },
    'принято'
);

/* 6. Сумма 0. */
$tests->exception(
    'Нулевая сумма отклонена',
    'validateAmount(0)',
    static fn(): mixed => validateAmount(0.0),
    InvalidArgumentException::class,
    'больше нуля'
);

/* 7. Сумма выше максимума. */
$tests->exception(
    'Сумма выше максимума отклонена',
    'validateAmount(1 100 000 000)',
    static fn(): mixed => validateAmount(1_100_000_000.0),
    InvalidArgumentException::class,
    'превышать'
);

/* 8. Отрицательный курс. */
$tests->exception(
    'Отрицательный курс отклонён',
    'validateRate(-470)',
    static fn(): mixed => validateRate(-470.0),
    InvalidArgumentException::class,
    'больше нуля'
);

/* 9. Комиссия сверх лимита. */
$tests->exception(
    'Комиссия 15 % отклонена',
    'validateCommission(15)',
    static fn(): mixed => validateCommission(15.0),
    InvalidArgumentException::class,
    'от 0 до 10'
);

/* 10. Неизвестная валютная пара. */
$tests->exception(
    'Нет курса для пары USD/GBP',
    'resolveRate("USD", "GBP", $rates)',
    static fn(): mixed => resolveRate('USD', 'GBP', $rates),
    DomainException::class,
    'не задан'
);

/* 11. Пустое имя клиента. */
$tests->exception(
    'Пустое имя клиента отклонено',
    'buildReceipt("   ", 100, "USD", 470, 0)',
    static fn(): mixed => buildReceipt('   ', 100.0, 'USD', 470.0, 0.0),
    InvalidArgumentException::class,
    'Имя клиента'
);

/* 12. Пустой набор для статистики. */
$tests->exception(
    'Статистика по пустому набору запрещена',
    'summarize([], fn)',
    static fn(): mixed => summarize([], static fn(float $value): float => $value),
    InvalidArgumentException::class,
    'пустому набору'
);

/* 13. Nullable-параметр: комментарий не передан. */
$tests->value(
    'Nullable-комментарий отсутствует',
    'buildReceipt(..., null)',
    static function (): string {
        $receipt = buildReceipt('Алия', 100.0, 'USD', 470.0, 0.0, null);

        return str_contains($receipt, 'Комментарий') ? 'есть комментарий' : 'без комментария';
    },
    'без комментария'
);

/* 13a. Квитанция в валюте получения, отличной от тенге. */
$tests->value(
    'Квитанция печатает валюту получения',
    'buildReceipt(..., "EUR")',
    static fn(): bool => str_contains(
        buildReceipt('Алия', 100.0, 'USD', 0.9, 0.0, null, 'EUR'),
        'EUR'
    ),
    true
);

/* 13b. Категория по эквиваленту в тенге для обратного направления. */
$tests->value(
    'Категория 1 000 USD = средняя (470 000 KZT)',
    'classifyAmountInKzt(1000, "USD", $rates)',
    static fn(): string => classifyAmountInKzt(1000.0, 'USD', $rates),
    'средняя'
);

/* 14. Строгая типизация: строка вместо float. */
$tests->exception(
    'strict_types не принимает строку вместо float',
    'convert("100", 470.0)',
    static fn(): mixed => convert('100', 470.0),
    TypeError::class
);

/* 15. Callback-фильтр. */
$tests->value(
    'Фильтр операций от 50 000 KZT',
    'filterOperations([1000, 50000, 120000], 50000)',
    static fn(): int => count(filterOperations(
        [['net' => 1000.0], ['net' => 50000.0], ['net' => 120000.0]],
        50000.0
    )),
    2
);

/* 16. Замыкание с use. */
$tests->value(
    'Замыкание запоминает курс через use',
    '$quote = function (float $v) use ($rate)',
    static function (): float {
        $rate = 470.0;
        $quote = static function (float $value) use ($rate): float {
            return convert($value, $rate);
        };

        return $quote(100.0);
    },
    47000.0
);

/* 17. Стрелочная функция как callback для usort. */
$tests->value(
    'Сортировка операций по убыванию суммы',
    'usort($list, fn($a, $b) => $b["net"] <=> $a["net"])',
    static function (): float {
        $list = [['net' => 100.0], ['net' => 300.0], ['net' => 200.0]];
        usort($list, static fn(array $left, array $right): int => $right['net'] <=> $left['net']);

        return $list[0]['net'];
    },
    300.0
);

/* 18. summarize с двумя разными селекторами. */
$tests->value(
    'summarize: максимум по селектору суммы',
    'summarize($items, fn => $item["amount"])["max"]',
    static fn(): float => summarize(
        [
            ['amount' => 100.0, 'net' => 980.0],
            ['amount' => 300.0, 'net' => 2940.0],
            ['amount' => 200.0, 'net' => 1960.0],
        ],
        static fn(array $item): float => $item['amount']
    )['max'],
    300.0
);

$tests->value(
    'summarize: среднее по селектору net',
    'summarize($items, fn => $item["net"])["avg"]',
    static fn(): float => summarize(
        [
            ['amount' => 100.0, 'net' => 980.0],
            ['amount' => 300.0, 'net' => 2940.0],
            ['amount' => 200.0, 'net' => 1960.0],
        ],
        static fn(array $item): float => $item['net']
    )['avg'],
    1960.0
);

/* 19. Union-тип и nullable-параметр форматирования. */
$tests->value(
    'formatMoney принимает int (union-тип)',
    'formatMoney(1234, "KZT")',
    static fn(): string => formatMoney(1234, 'KZT'),
    '1 234.00 KZT'
);

$tests->value(
    'formatMoney с явным числом знаков',
    'formatMoney(1234.5, "USD", 2)',
    static fn(): string => formatMoney(1234.5, 'USD', 2),
    '1 234.50 USD'
);

/* 20. Variadic-параметр. */
$tests->value(
    'sumAmounts суммирует пакет платежей',
    'sumAmounts(100, 200, 300)',
    static fn(): float => sumAmounts(100.0, 200.0, 300.0),
    600.0
);

$tests->exception(
    'sumAmounts без аргументов отклонён',
    'sumAmounts()',
    static fn(): mixed => sumAmounts(),
    InvalidArgumentException::class,
    'хотя бы один'
);

$rows = $tests->rows();
$passed = $tests->passed();
$failed = $tests->failed();
$total = count($rows);
$success = $failed === 0;

if (PHP_SAPI === 'cli') {
    $line = str_repeat('-', 108);
    echo "Лабораторная работа №4. Вариант 20 — тесты библиотеки функций\n";
    echo "PHP " . PHP_VERSION . ", strict_types=1\n";
    echo $line . "\n";
    foreach ($rows as $index => $row) {
        printf(
            "%2d. [%s] %s\n    вход:     %s\n    ожидалось: %s\n    получено:  %s\n",
            $index + 1,
            $row['ok'] ? 'OK  ' : 'FAIL',
            $row['name'],
            $row['input'],
            $row['expected'],
            $row['actual']
        );
    }
    echo $line . "\n";
    printf("Итого: %d тестов, успешно %d, провалено %d\n", $total, $passed, $failed);
    echo $success ? "РЕЗУЛЬТАТ: все тесты пройдены\n" : "РЕЗУЛЬТАТ: есть проваленные тесты\n";
    exit($success ? 0 : 1);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёт о тестах — лабораторная работа №4</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="page">
    <header class="hero">
        <p class="eyebrow">Лабораторная работа №4 · Вариант 20</p>
        <h1>Отчёт о тестах</h1>
        <p class="lead">
            Проверка контрактов библиотеки функций: обычные расчёты, границы диапазонов,
            ошибочные аргументы, nullable- и union-типы, замыкание и callback-функции.
        </p>
        <div class="chips">
            <span class="chip">PHP <?= testEscape(PHP_VERSION) ?></span>
            <span class="chip">strict_types=1</span>
            <span class="chip"><?= testEscape((string) $total) ?> сценариев</span>
        </div>
    </header>

    <main>
        <section class="section">
            <div class="section-head">
                <div>
                    <h2>Результаты выполнения</h2>
                    <p>Ошибочный тест считается успешным, если выброшено ожидаемое исключение.</p>
                </div>
                <p><a href="index.php">← Вернуться к калькулятору</a></p>
            </div>

            <div class="table-wrap">
                <table class="tests-table">
                    <thead>
                    <tr>
                        <th>№</th>
                        <th>Сценарий</th>
                        <th>Входные данные</th>
                        <th>Ожидаемо</th>
                        <th>Фактически</th>
                        <th>Статус</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $index => $row): ?>
                        <tr>
                            <td><?= testEscape((string) ($index + 1)) ?></td>
                            <td><?= testEscape($row['name']) ?></td>
                            <td><code><?= testEscape($row['input']) ?></code></td>
                            <td><?= testEscape($row['expected']) ?></td>
                            <td><?= testEscape($row['actual']) ?></td>
                            <td class="status <?= $row['ok'] ? 'ok' : 'fail' ?>">
                                <?= $row['ok'] ? 'PASS' : 'FAIL' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="test-summary">
                <div class="stat">
                    <span>Всего</span>
                    <strong><?= testEscape((string) $total) ?></strong>
                </div>
                <div class="stat">
                    <span>Пройдено</span>
                    <strong class="ok"><?= testEscape((string) $passed) ?></strong>
                </div>
                <div class="stat">
                    <span>Провалено</span>
                    <strong class="<?= $failed === 0 ? 'ok' : 'fail' ?>"><?= testEscape((string) $failed) ?></strong>
                </div>
                <div class="stat">
                    <span>Итог</span>
                    <strong class="<?= $success ? 'ok' : 'fail' ?>">
                        <?= $success ? 'все пройдены' : 'есть ошибки' ?>
                    </strong>
                </div>
            </div>

            <p class="note">
                Запуск в терминале: <code>php tests.php</code>. Код возврата 0 означает,
                что все сценарии пройдены; 1 — есть проваленные проверки.
            </p>
        </section>
    </main>

    <footer class="footer">
        <span>PHP <?= testEscape(PHP_VERSION) ?> · declare(strict_types=1)</span>
        <span><a href="index.php">Калькулятор обмена</a></span>
    </footer>
</div>
</body>
</html>
