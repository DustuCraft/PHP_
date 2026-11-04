<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Enums\Category;

/**
 * Позиция меню: неизменяемый объект значения.
 */
final readonly class Product
{
    public function __construct(
        public string $id,
        public string $title,
        public int $price,
        public Category $category,
        public string $description = '',
    ) {
        if (trim($title) === '') {
            throw new \InvalidArgumentException('Название позиции обязательно.');
        }

        if ($price < 0) {
            throw new \InvalidArgumentException('Цена не может быть отрицательной.');
        }
    }
}
