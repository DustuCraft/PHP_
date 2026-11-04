<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * Позиция корзины: товар, цена на момент заказа и количество.
 */
final readonly class OrderItem
{
    public function __construct(
        public string $id,
        public string $title,
        public int $price,
        public int $quantity,
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Количество должно быть не меньше одного.');
        }

        if ($price < 0) {
            throw new \InvalidArgumentException('Цена не может быть отрицательной.');
        }
    }

    public function total(): int
    {
        return $this->price * $this->quantity;
    }
}
