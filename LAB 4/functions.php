<?php

declare(strict_types=1);

/**
 * Лабораторная работа №4. Вариант 20 — «Конвертация валют».
 *
 * Библиотека чистых типизированных функций:
 *  - не содержит HTML-вывода и не зависит от суперглобальных переменных;
 *  - не использует global: курсы, суммы и настройки передаются параметрами;
 *  - сообщает о нарушении контракта исключениями.
 */

/** Максимальная сумма операции в исходной валюте. */
const MAX_AMOUNT = 1_000_000_000.0;

/** Максимальный допустимый курс. */
const MAX_RATE = 100_000.0;

/** Максимальная комиссия обменного пункта, %. */
const MAX_COMMISSION_PERCENT = 10.0;

/** Шаблон трёхбуквенного кода валюты (ISO 4217). */
const CURRENCY_PATTERN = '/^[A-Z]{3}$/';

/**
 * Проверяет сумму операции.
 *
 * @param float  $amount Сумма в исходной валюте, должна быть в диапазоне (0; 1 000 000 000].
 * @param string $field  Имя проверяемого поля для понятного текста ошибки.
 * @throws InvalidArgumentException Если сумма не является конечным числом, не положительна
 *                                  или превышает допустимый максимум.
 */
function validateAmount(float $amount, string $field = 'Сумма'): void
{
    if (!is_finite($amount)) {
        throw new InvalidArgumentException("{$field}: значение должно быть конечным числом");
    }
    if ($amount <= 0.0) {
        throw new InvalidArgumentException("{$field} должна быть больше нуля");
    }
    if ($amount > MAX_AMOUNT) {
        throw new InvalidArgumentException(
            "{$field} не может превышать " . number_format(MAX_AMOUNT, 0, '.', ' ') . ' единиц'
        );
    }
}

/**
 * Проверяет обменный курс.
 *
 * @param float  $rate Курс за одну единицу исходной валюты, (0; 100 000].
 * @param string $pair Подпись пары валют, например «USD/KZT».
 * @throws InvalidArgumentException Если курс не положителен или выходит за диапазон.
 */
function validateRate(float $rate, string $pair = 'Курс'): void
{
    if (!is_finite($rate)) {
        throw new InvalidArgumentException("{$pair}: курс должен быть конечным числом");
    }
    if ($rate <= 0.0) {
        throw new InvalidArgumentException("{$pair}: курс должен быть больше нуля");
    }
    if ($rate > MAX_RATE) {
        throw new InvalidArgumentException(
            "{$pair}: курс не может превышать " . number_format(MAX_RATE, 0, '.', ' ')
        );
    }
}

/**
 * Проверяет процент комиссии.
 *
 * @param float $percent Комиссия обменного пункта в процентах, [0; 10].
 * @throws InvalidArgumentException Если процент отрицателен или превышает лимит.
 */
function validateCommission(float $percent): void
{
    if (!is_finite($percent) || $percent < 0.0 || $percent > MAX_COMMISSION_PERCENT) {
        throw new InvalidArgumentException(
            'Комиссия должна быть от 0 до ' . MAX_COMMISSION_PERCENT . ' %'
        );
    }
}

/**
 * Считает комиссию обменного пункта.
 *
 * Комиссия берётся с суммы, уже пересчитанной в валюту получения,
 * но не может быть меньше минимального значения и не может превысить
 * саму сумму обмена.
 *
 * @param float $convertedAmount Сумма после конвертации, > 0.
 * @param float $percent         Процент комиссии, [0; 10].
 * @param float $minFee          Минимальная комиссия, >= 0.
 * @return float Комиссия, округлённая до копеек.
 * @throws InvalidArgumentException При некорректной сумме, проценте или минимальной комиссии.
 */
function commissionFee(float $convertedAmount, float $percent, float $minFee = 0.0): float
{
    if (!is_finite($convertedAmount) || $convertedAmount <= 0.0) {
        throw new InvalidArgumentException('Сумма после конвертации должна быть больше нуля');
    }
    validateCommission($percent);

    if (!is_finite($minFee) || $minFee < 0.0) {
        throw new InvalidArgumentException('Минимальная комиссия не может быть отрицательной');
    }

    $fee = $convertedAmount * $percent / 100.0;
    $fee = max($fee, $minFee);
    $fee = min($fee, $convertedAmount);

    return round($fee, 2);
}

/**
 * Выполняет полный расчёт обменной операции.
 *
 * @param float $amount            Сумма в исходной валюте, > 0.
 * @param float $rate              Курс исходной валюты к валюте получения, > 0.
 * @param float $commissionPercent Процент комиссии, [0; 10].
 * @param float $minFee            Минимальная комиссия в валюте получения, >= 0.
 * @return array{amount: float, rate: float, gross: float, fee: float, net: float, category: string}
 *         Сумма, курс, сумма до комиссии, комиссия, сумма к зачислению и категория операции.
 * @throws InvalidArgumentException Если хотя бы один аргумент нарушает контракт.
 */
function conversionDetails(
    float $amount,
    float $rate,
    float $commissionPercent = 0.0,
    float $minFee = 0.0
): array {
    validateAmount($amount, 'Сумма обмена');
    validateRate($rate, 'Курс обмена');
    validateCommission($commissionPercent);

    $gross = round($amount * $rate, 2);
    $fee = commissionFee($gross, $commissionPercent, $minFee);
    $net = round($gross - $fee, 2);

    return [
        'amount' => $amount,
        'rate' => $rate,
        'gross' => $gross,
        'fee' => $fee,
        'net' => $net,
        'category' => classifyConversion($net),
    ];
}

/**
 * Конвертирует сумму по заданному курсу с учётом комиссии.
 *
 * @param float $amount            Сумма в исходной валюте, > 0.
 * @param float $rate              Курс, > 0.
 * @param float $commissionPercent Комиссия, [0; 10] %.
 * @param float $minFee            Минимальная комиссия в валюте получения, >= 0.
 * @return float Сумма к зачислению, округлённая до копеек.
 * @throws InvalidArgumentException Если аргументы нарушают контракт.
 */
function convert(
    float $amount,
    float $rate,
    float $commissionPercent = 0.0,
    float $minFee = 0.0
): float {
    return conversionDetails($amount, $rate, $commissionPercent, $minFee)['net'];
}

/**
 * Находит курс пары валют в явно переданном справочнике.
 *
 * @param string               $from  Код исходной валюты, например «USD».
 * @param string               $to    Код валюты получения, например «KZT».
 * @param array<string, float> $rates Справочник курсов вида «USD/KZT» => 470.0.
 * @return float Курс пары.
 * @throws InvalidArgumentException Если код валюты имеет неверный формат.
 * @throws DomainException          Если курс запрошенной пары отсутствует в справочнике.
 */
function resolveRate(string $from, string $to, array $rates): float
{
    $from = strtoupper(trim($from));
    $to = strtoupper(trim($to));

    if (preg_match(CURRENCY_PATTERN, $from) !== 1) {
        throw new InvalidArgumentException("Некорректный код исходной валюты: «{$from}»");
    }
    if (preg_match(CURRENCY_PATTERN, $to) !== 1) {
        throw new InvalidArgumentException("Некорректный код валюты получения: «{$to}»");
    }
    if ($from === $to) {
        return 1.0;
    }

    $pair = $from . '/' . $to;
    if (array_key_exists($pair, $rates)) {
        $rate = (float) $rates[$pair];
        validateRate($rate, "Курс {$pair}");

        return $rate;
    }

    // Обратный курс: если задан «USD/KZT», то «KZT/USD» считается как 1 / курс.
    $inversePair = $to . '/' . $from;
    if (array_key_exists($inversePair, $rates)) {
        $rate = 1.0 / (float) $rates[$inversePair];
        validateRate($rate, "Курс {$pair}");

        return $rate;
    }

    // Кросс-курс через тенге: USD → EUR = (USD/KZT) / (EUR/KZT).
    $fromToKzt = $from . '/KZT';
    $toToKzt = $to . '/KZT';
    if (array_key_exists($fromToKzt, $rates) && array_key_exists($toToKzt, $rates)) {
        $rate = (float) $rates[$fromToKzt] / (float) $rates[$toToKzt];
        validateRate($rate, "Курс {$pair}");

        return $rate;
    }

    throw new DomainException("Курс для пары {$pair} не задан в справочнике");
}

/**
 * Относит сумму к категории обменной операции.
 *
 * @param float $amountKzt Сумма в тенге, >= 0.
 * @return string «крупная», «средняя», «небольшая» или «мелкая».
 * @throws InvalidArgumentException Если сумма отрицательна или не является конечным числом.
 */
function classifyConversion(float $amountKzt): string
{
    if (!is_finite($amountKzt) || $amountKzt < 0.0) {
        throw new InvalidArgumentException('Сумма для классификации должна быть неотрицательной');
    }

    return match (true) {
        $amountKzt >= 1_000_000.0 => 'крупная',
        $amountKzt >= 100_000.0 => 'средняя',
        $amountKzt >= 10_000.0 => 'небольшая',
        default => 'мелкая',
    };
}

/**
 * Форматирует денежную сумму для вывода.
 *
 * @param float|int $amount   Сумма (union-тип допускает и int).
 * @param string    $currency Код валюты ISO 4217.
 * @param int|null  $decimals Количество знаков после запятой; null — по правилам валюты
 *                            (0 — для JPY/KRW/VND, иначе 2).
 * @return string Например, «1 234.50 USD» или «47 000.00 KZT».
 * @throws InvalidArgumentException При неверном коде валюты или количестве знаков.
 */
function formatMoney(float|int $amount, string $currency, ?int $decimals = null): string
{
    $code = strtoupper(trim($currency));

    if (preg_match(CURRENCY_PATTERN, $code) !== 1) {
        throw new InvalidArgumentException("Некорректный код валюты: «{$currency}»");
    }
    if (!is_finite((float) $amount)) {
        throw new InvalidArgumentException('Сумма для форматирования должна быть конечным числом');
    }
    if ($decimals !== null && ($decimals < 0 || $decimals > 4)) {
        throw new InvalidArgumentException('Количество знаков после запятой должно быть от 0 до 4');
    }

    $decimals ??= in_array($code, ['JPY', 'KRW', 'VND'], true) ? 0 : 2;

    return number_format((float) $amount, $decimals, '.', ' ') . ' ' . $code;
}

/**
 * Относит сумму в любой валюте к категории по её эквиваленту в тенге.
 *
 * @param float                $amount   Сумма в исходной валюте, > 0.
 * @param string               $currency Код валюты ISO 4217.
 * @param array<string, float> $rates    Справочник курсов.
 * @return string «крупная», «средняя», «небольшая» или «мелкая».
 * @throws InvalidArgumentException Если сумма или код валюты некорректны.
 * @throws DomainException          Если курс валюты к тенге не задан.
 */
function classifyAmountInKzt(float $amount, string $currency, array $rates): string
{
    validateAmount($amount, 'Сумма для классификации');

    return classifyConversion($amount * resolveRate($currency, 'KZT', $rates));
}

/**
 * Преобразует запись об операции в полный расчёт в тенге.
 *
 * @param array<string, mixed> $operation Запись с полями client, amount, currency, commission, min_fee.
 * @param array<string, float> $rates     Справочник курсов.
 * @return array<string, mixed> Исходные данные операции и результат conversionDetails().
 * @throws InvalidArgumentException Если в записи нет обязательного поля.
 */
function convertOperation(array $operation, array $rates): array
{
    foreach (['client', 'amount', 'currency', 'commission'] as $key) {
        if (!array_key_exists($key, $operation)) {
            throw new InvalidArgumentException("В операции отсутствует поле «{$key}»");
        }
    }

    $currency = strtoupper(trim((string) $operation['currency']));
    $amount = (float) $operation['amount'];
    $details = conversionDetails(
        $amount,
        resolveRate($currency, 'KZT', $rates),
        (float) $operation['commission'],
        (float) ($operation['min_fee'] ?? 0.0)
    );

    return [
        'client' => trim((string) $operation['client']),
        'currency' => $currency,
        ...$details,
        'category' => classifyAmountInKzt($amount, $currency, $rates),
    ];
}

/**
 * Отбирает операции с суммой к зачислению не меньше порога.
 *
 * @param array<int, array<string, mixed>> $operations Операции с полем net.
 * @param float                            $minAmount  Порог в тенге, >= 0.
 * @return array<int, array<string, mixed>> Отфильтрованный список с переиндексированными ключами.
 * @throws InvalidArgumentException Если порог отрицателен.
 */
function filterOperations(array $operations, float $minAmount = 0.0): array
{
    if (!is_finite($minAmount) || $minAmount < 0.0) {
        throw new InvalidArgumentException('Порог отбора не может быть отрицательным');
    }

    return array_values(array_filter(
        $operations,
        fn(array $operation): bool => (float) ($operation['net'] ?? 0.0) >= $minAmount
    ));
}

/**
 * Возвращает статистику числового показателя набора записей.
 *
 * @param array<int, mixed> $items    Непустой набор записей.
 * @param callable          $selector Функция извлечения числового показателя.
 * @return array{count: int, min: float, max: float, sum: float, avg: float, median: float}
 * @throws InvalidArgumentException Если набор пуст или селектор вернул не число.
 */
function summarize(array $items, callable $selector): array
{
    if ($items === []) {
        throw new InvalidArgumentException('Нельзя вычислить статистику по пустому набору');
    }

    $values = [];
    foreach ($items as $index => $item) {
        $value = $selector($item);
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Селектор вернул нечисловое значение для элемента №{$index}");
        }
        $values[] = (float) $value;
    }

    sort($values);
    $count = count($values);
    $sum = array_sum($values);

    return [
        'count' => $count,
        'min' => $values[0],
        'max' => $values[$count - 1],
        'sum' => round($sum, 2),
        'avg' => round($sum / $count, 2),
        'median' => $count % 2 === 1
            ? $values[intdiv($count, 2)]
            : round(($values[$count / 2 - 1] + $values[$count / 2]) / 2, 2),
    ];
}

/**
 * Формирует текст квитанции обмена.
 *
 * @param string      $client         Имя клиента, непустая строка.
 * @param float       $amount         Сумма в исходной валюте, > 0.
 * @param string      $currency       Код исходной валюты.
 * @param float       $rate           Курс к валюте получения, > 0.
 * @param float       $fee            Комиссия в валюте получения, >= 0.
 * @param string|null $comment        Необязательный комментарий (nullable-тип).
 * @param string      $targetCurrency Код валюты получения.
 * @param string|null $category       Категория операции; null — рассчитать по сумме к зачислению.
 * @return string Многострочный текст квитанции.
 * @throws InvalidArgumentException Если имя пустое, сумма, курс или коды валют некорректны,
 *                                  либо комиссия превышает сумму обмена.
 */
function buildReceipt(
    string $client,
    float $amount,
    string $currency,
    float $rate,
    float $fee,
    ?string $comment = null,
    string $targetCurrency = 'KZT',
    ?string $category = null
): string {
    $client = trim($client);

    if ($client === '') {
        throw new InvalidArgumentException('Имя клиента обязательно для квитанции');
    }
    validateAmount($amount, 'Сумма обмена');
    validateRate($rate, 'Курс обмена');

    $currency = strtoupper(trim($currency));
    $targetCurrency = strtoupper(trim($targetCurrency));
    if (preg_match(CURRENCY_PATTERN, $currency) !== 1) {
        throw new InvalidArgumentException("Некорректный код валюты: «{$currency}»");
    }
    if (preg_match(CURRENCY_PATTERN, $targetCurrency) !== 1) {
        throw new InvalidArgumentException("Некорректный код валюты получения: «{$targetCurrency}»");
    }

    $gross = round($amount * $rate, 2);
    if (!is_finite($fee) || $fee < 0.0) {
        throw new InvalidArgumentException('Комиссия не может быть отрицательной');
    }
    if ($fee > $gross) {
        throw new InvalidArgumentException('Комиссия не может превышать сумму обмена');
    }

    $net = round($gross - $fee, 2);
    $category ??= classifyConversion($net);
    $lines = [
        'КВИТАНЦИЯ НА ОБМЕН ВАЛЮТЫ',
        str_repeat('=', 44),
        'Клиент:    ' . $client,
        'Операция:  ' . formatMoney($amount, $currency) . ' × ' . number_format($rate, 4, '.', ' ')
            . ' = ' . formatMoney($gross, $targetCurrency),
        'Комиссия:  ' . formatMoney($fee, $targetCurrency),
        'К зачислению: ' . formatMoney($net, $targetCurrency),
        'Категория: ' . $category,
    ];

    if ($comment !== null && trim($comment) !== '') {
        $lines[] = 'Комментарий: ' . trim($comment);
    }

    $lines[] = str_repeat('=', 44);

    return implode(PHP_EOL, $lines);
}

/**
 * Суммирует пакет платежей (variadic-параметр).
 *
 * @param float ...$amounts Суммы платежей, каждая > 0.
 * @return float Итог, округлённый до копеек.
 * @throws InvalidArgumentException Если пакет пуст или содержит некорректную сумму.
 */
function sumAmounts(float ...$amounts): float
{
    if ($amounts === []) {
        throw new InvalidArgumentException('Нужен хотя бы один платёж для суммирования');
    }

    $total = 0.0;
    foreach ($amounts as $index => $amount) {
        validateAmount($amount, 'Платёж №' . ($index + 1));
        $total += $amount;
    }

    return round($total, 2);
}
