<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Enums\DeliveryMethod;

/**
 * Заказ: корзина, способ получения и реквизиты доставки.
 *
 * Для курьера обязателен адрес, для самовывоза — пункт выдачи.
 * Проверку выполняет конкретный калькулятор, который знает свои требования.
 */
final readonly class Order
{
    /** @param list<OrderItem> $items */
    public function __construct(
        public array $items,
        public DeliveryMethod $method,
        public ?Address $address = null,
        public ?PickupPoint $pickupPoint = null,
    ) {
        if ($items === []) {
            throw new \InvalidArgumentException('Корзина пуста: добавьте хотя бы одну позицию.');
        }
    }

    public function subtotal(): int
    {
        $sum = 0;

        foreach ($this->items as $item) {
            $sum += $item->total();
        }

        return $sum;
    }

    public function count(): int
    {
        $count = 0;

        foreach ($this->items as $item) {
            $count += $item->quantity;
        }

        return $count;
    }
}
