<?php

declare(strict_types=1);

/**
 * Лабораторная работа №4. Вариант 20 — «Конвертация валют».
 * Страница-представление: только читает ввод, вызывает функции из functions.php
 * и выводит HTML. Все расчёты выполняются в типизированных функциях.
 */

require_once __DIR__ . '/functions.php';

/**
 * Читает строковый параметр запроса.
 *
 * @param string $key     Имя параметра.
 * @param string $default Значение по умолчанию.
 * @return string Очищенная строка.
 */
function requestValue(string $key, string $default): string
{
    $value = $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

/**
 * Читает числовой параметр запроса.
 *
 * @param string $key     Имя параметра.
 * @param string $default Значение по умолчанию.
 * @param string $field   Подпись поля для сообщения об ошибке.
 * @return float Разобранное число.
 * @throws InvalidArgumentException Если значение не является числом.
 */
function requestNumber(string $key, string $default, string $field): float
{
    $raw = requestValue($key, $default);
    $number = filter_var($raw, FILTER_VALIDATE_FLOAT);

    if ($raw === '' || $number === false || !is_finite((float) $number)) {
        throw new InvalidArgumentException("Поле «{$field}» должно содержать число");
    }

    return (float) $number;
}

/**
 * Экранирует текст для безопасного вывода в HTML.
 *
 * @param string $value Текст.
 * @return string Экранированный текст.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Справочник курсов: сколько тенге стоит одна единица валюты. */
$rates = [
    'USD/KZT' => 470.00,
    'EUR/KZT' => 512.00,
    'RUB/KZT' => 5.85,
    'CNY/KZT' => 65.40,
];

$currencies = ['USD', 'EUR', 'RUB', 'CNY', 'KZT'];

/** Демонстрационный портфель обменных операций. */
$operations = [
    ['client' => 'Алия', 'amount' => 250.0, 'currency' => 'USD', 'commission' => 1.5, 'min_fee' => 200.0],
    ['client' => 'Данияр', 'amount' => 120.0, 'currency' => 'EUR', 'commission' => 1.0, 'min_fee' => 0.0],
    ['client' => 'Мадина', 'amount' => 50000.0, 'currency' => 'RUB', 'commission' => 2.0, 'min_fee' => 300.0],
    ['client' => 'Тимур', 'amount' => 800.0, 'currency' => 'CNY', 'commission' => 1.5, 'min_fee' => 0.0],
    ['client' => 'Асель', 'amount' => 90.0, 'currency' => 'USD', 'commission' => 0.5, 'min_fee' => 150.0],
    ['client' => 'Ерасыл', 'amount' => 300.0, 'currency' => 'EUR', 'commission' => 3.0, 'min_fee' => 500.0],
];

$form = [
    'client' => requestValue('client', 'Алия'),
    'amount' => requestValue('amount', '250'),
    'from' => strtoupper(requestValue('from', 'USD')),
    'to' => strtoupper(requestValue('to', 'KZT')),
    'commission' => requestValue('commission', '1.5'),
    'min_fee' => requestValue('min_fee', '200'),
    'comment' => requestValue('comment', 'перевод семье'),
];

$error = null;
$main = null;

try {
    foreach (['from', 'to'] as $side) {
        if (!in_array($form[$side], $currencies, true)) {
            throw new DomainException("Валюта {$form[$side]} не обслуживается обменным пунктом");
        }
    }

    $amount = requestNumber('amount', '250', 'Сумма');
    $commission = requestNumber('commission', '1.5', 'Комиссия');
    $minFee = requestNumber('min_fee', '200', 'Минимальная комиссия');
    $rate = resolveRate($form['from'], $form['to'], $rates);
    $category = classifyAmountInKzt($amount, $form['from'], $rates);

    // Замыкание фиксирует курс и условия обмена через use.
    $quote = function (float $value) use ($rate, $commission, $minFee): array {
        return conversionDetails($value, $rate, $commission, $minFee);
    };

    $details = $quote($amount);
    $comment = $form['comment'] === '' ? null : $form['comment'];
    $receipt = buildReceipt(
        $form['client'],
        $amount,
        $form['from'],
        $rate,
        $details['fee'],
        $comment,
        $form['to'],
        $category
    );

    $main = [
        'amount' => $amount,
        'from' => $form['from'],
        'to' => $form['to'],
        'commission' => $commission,
        'minFee' => $minFee,
        'rate' => $rate,
        'category' => $category,
        'details' => $details,
        'receipt' => $receipt,
    ];
} catch (InvalidArgumentException | DomainException $exception) {
    $error = $exception->getMessage();
}

$demoError = null;
$converted = [];
$large = [];
$netStats = [];
$packageTotal = 0.0;

try {
    // Стрелочная функция-обёртка передаёт каждую операцию в типизированный расчёт.
    $converted = array_map(
        fn(array $operation): array => convertOperation($operation, $rates),
        $operations
    );

    usort($converted, fn(array $left, array $right): int => $right['net'] <=> $left['net']);

    $large = filterOperations($converted, 50000.0);
    $netStats = summarize($converted, fn(array $operation): float => $operation['net']);
    $packageTotal = sumAmounts(...array_column($converted, 'net'));
} catch (InvalidArgumentException | DomainException $exception) {
    $demoError = $exception->getMessage();
}

/**
 * Возвращает CSS-класс категории операции.
 *
 * @param string $category Категория из classifyConversion().
 * @return string Класс для оформления.
 */
function categoryClass(string $category): string
{
    return match ($category) {
        'крупная' => 'large',
        'средняя' => 'medium',
        default => '',
    };
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конвертация валют — лабораторная работа №4</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="page">
    <header class="hero">
        <p class="eyebrow">Лабораторная работа №4 · Вариант 20</p>
        <h1>Конвертация валют</h1>
        <p class="lead">
            Набор типизированных функций обменного пункта: проверка суммы и курса,
            расчёт комиссии, конвертация, классификация операции и формирование квитанции.
            Расчёты отделены от HTML-вывода, зависимости передаются явно.
        </p>
    </header>

    <main>
        <div class="layout">
            <section class="card" aria-labelledby="form-title">
                <h2 id="form-title">Параметры обмена</h2>
                <p class="hint">Курс считается по справочнику: прямой, обратный или кросс-курс через тенге.</p>

                <form method="get" action="index.php">
                    <div class="field">
                        <label for="client">Клиент</label>
                        <input type="text" id="client" name="client" value="<?= e($form['client']) ?>" maxlength="60">
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="from">Из валюты</label>
                            <select id="from" name="from">
                                <?php foreach ($currencies as $currency): ?>
                                    <option value="<?= e($currency) ?>" <?= $currency === $form['from'] ? 'selected' : '' ?>>
                                        <?= e($currency) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="to">В валюту</label>
                            <select id="to" name="to">
                                <?php foreach ($currencies as $currency): ?>
                                    <option value="<?= e($currency) ?>" <?= $currency === $form['to'] ? 'selected' : '' ?>>
                                        <?= e($currency) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="amount">Сумма</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0.01"
                                   value="<?= e($form['amount']) ?>">
                        </div>
                        <div class="field">
                            <label for="commission">Комиссия, %</label>
                            <input type="number" id="commission" name="commission" step="0.1" min="0" max="10"
                                   value="<?= e($form['commission']) ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label for="min_fee">Мин. комиссия (в валюте получения)</label>
                        <input type="number" id="min_fee" name="min_fee" step="10" min="0"
                               value="<?= e($form['min_fee']) ?>">
                    </div>

                    <div class="field">
                        <label for="comment">Комментарий (необязательно)</label>
                        <input type="text" id="comment" name="comment" value="<?= e($form['comment']) ?>" maxlength="120">
                    </div>

                    <button type="submit">Рассчитать обмен</button>
                </form>
            </section>

            <section aria-labelledby="result-title">
                <?php if ($error !== null): ?>
                    <div class="alert" role="alert">
                        <strong>Операция отклонена.</strong>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($main !== null): ?>
                    <div class="card">
                        <div class="result-head">
                            <div>
                                <h2 id="result-title">К зачислению</h2>
                                <p class="result-amount"><?= e(formatMoney($main['details']['net'], $main['to'])) ?></p>
                                <p class="result-sub">
                                    1 <?= e($main['from']) ?> = <?= e(number_format($main['rate'], 4, '.', ' ')) ?>
                                    <?= e($main['to']) ?> ·
                                    <?= e(formatMoney($main['amount'], $main['from'])) ?> →
                                    <?= e(formatMoney($main['details']['net'], $main['to'])) ?>
                                </p>
                            </div>
                            <span class="badge"><?= e($main['category']) ?> сумма</span>
                        </div>

                        <div class="kv">
                            <div class="kv-row">
                                <span>Сумма до комиссии</span>
                                <strong><?= e(formatMoney($main['details']['gross'], $main['to'])) ?></strong>
                            </div>
                            <div class="kv-row">
                                <span>Комиссия (<?= e((string) $main['commission']) ?> %, минимум
                                    <?= e(formatMoney($main['minFee'], $main['to'])) ?>)</span>
                                <strong>−<?= e(formatMoney($main['details']['fee'], $main['to'])) ?></strong>
                            </div>
                            <div class="kv-row">
                                <span>Сумма списания</span>
                                <strong><?= e(formatMoney($main['amount'], $main['from'])) ?></strong>
                            </div>
                            <div class="kv-row">
                                <span>Категория операции</span>
                                <strong><?= e($main['category']) ?></strong>
                            </div>
                        </div>

                        <pre class="receipt"><?= e($main['receipt']) ?></pre>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <section class="section" aria-labelledby="portfolio-title">
            <div class="section-head">
                <div>
                    <h2 id="portfolio-title">Портфель обменных операций</h2>
                    <p>Шесть клиентов; суммы пересчитаны стрелочной функцией и отсортированы по сумме к зачислению.</p>
                </div>
                <p>Итого по портфелю: <strong><?= e(formatMoney($packageTotal, 'KZT')) ?></strong></p>
            </div>

            <?php if ($demoError !== null): ?>
                <div class="alert" role="alert"><?= e($demoError) ?></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>№</th>
                            <th>Клиент</th>
                            <th class="num">Сумма</th>
                            <th class="num">Курс</th>
                            <th class="num">До комиссии</th>
                            <th class="num">Комиссия</th>
                            <th class="num">К зачислению</th>
                            <th>Категория</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($converted as $index => $operation): ?>
                            <tr>
                                <td><?= e((string) ($index + 1)) ?></td>
                                <td class="name"><?= e($operation['client']) ?></td>
                                <td class="num"><?= e(formatMoney($operation['amount'], $operation['currency'])) ?></td>
                                <td class="num"><?= e(number_format($operation['rate'], 2, '.', ' ')) ?></td>
                                <td class="num"><?= e(formatMoney($operation['gross'], 'KZT')) ?></td>
                                <td class="num"><?= e(formatMoney($operation['fee'], 'KZT')) ?></td>
                                <td class="num"><strong><?= e(formatMoney($operation['net'], 'KZT')) ?></strong></td>
                                <td>
                                    <span class="cat <?= e(categoryClass($operation['category'])) ?>">
                                        <?= e($operation['category']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="stats">
                    <div class="stat">
                        <span>Операций</span>
                        <strong><?= e((string) $netStats['count']) ?></strong>
                    </div>
                    <div class="stat">
                        <span>Минимум, KZT</span>
                        <strong><?= e(formatMoney($netStats['min'], 'KZT')) ?></strong>
                    </div>
                    <div class="stat">
                        <span>Максимум, KZT</span>
                        <strong><?= e(formatMoney($netStats['max'], 'KZT')) ?></strong>
                    </div>
                    <div class="stat">
                        <span>Среднее, KZT</span>
                        <strong><?= e(formatMoney($netStats['avg'], 'KZT')) ?></strong>
                    </div>
                    <div class="stat">
                        <span>Отобрано ≥ 50 000 KZT</span>
                        <strong><?= e((string) count($large)) ?> из <?= e((string) count($converted)) ?></strong>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="footer">
        <span>PHP <?= e(PHP_VERSION) ?> · declare(strict_types=1) · без global</span>
        <span>
            <a href="tests.php">Отчёт о тестах</a> ·
            <a href="README.txt">README</a>
        </span>
    </footer>
</div>
</body>
</html>
