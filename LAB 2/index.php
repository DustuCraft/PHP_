<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

/**
 * Конвертер валют: тенге (KZT) в доллары США (USD).
 *
 * Требования задания:
 * - переменные разных типов + константа;
 * - арифметическое и строковое выражения;
 * - строгое сравнение (===);
 * - number_format() для вывода;
 * - корректная кодировка UTF-8.
 */

// Константа — курс обмена не должен изменяться в ходе выполнения программы
const EXCHANGE_RATE = 540.25;

// Переменные разных типов
$amountKzt = 150000;          // int  — сумма в тенге
$currencyName = 'Тенге';      // string — название валюты
$isRateActual = true;         // bool — признак актуальности курса

// Арифметическое выражение
$dollars = $amountKzt / EXCHANGE_RATE;

// Строковое выражение (конкатенация)
$resultLabel = $currencyName . ' → Доллары США';

// Строгое сравнение типов
if ($isRateActual === true) {
    $statusText = 'Курс актуален';
} else {
    $statusText = 'Курс устарел';
}

// Форматированный вывод
printf("<h2>%s</h2>\n", $resultLabel);
printf("<p>Сумма в тенге: %s KZT</p>\n", number_format($amountKzt, 0, ',', ' '));
printf("<p>Курс обмена: %s KZT за 1 USD</p>\n", number_format(EXCHANGE_RATE, 2, ',', ' '));
printf("<p>Сумма в долларах: %s USD</p>\n", number_format($dollars, 2, ',', ' '));
printf("<p>Статус: %s</p>\n", $statusText);
