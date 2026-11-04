<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Категории меню: фастфуд, комбо и напитки.
 */
enum Category: string
{
    case FastFood = 'fastfood';
    case Combo = 'combo';
    case Drink = 'drink';

    public function label(): string
    {
        return match ($this) {
            self::FastFood => 'Фастфуд',
            self::Combo => 'Комбо',
            self::Drink => 'Напитки',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FastFood => '🍔',
            self::Combo => '🍟',
            self::Drink => '🥤',
        };
    }
}
