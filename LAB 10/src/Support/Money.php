<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Форматирование денежных сумм в тенге.
 */
final class Money
{
    public static function format(int $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' ₸';
    }

    public static function formatDelta(int $amount): string
    {
        return $amount === 0 ? 'бесплатно' : self::format($amount);
    }
}
