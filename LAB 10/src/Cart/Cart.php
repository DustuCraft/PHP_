<?php

declare(strict_types=1);

namespace App\Cart;

use App\Catalog\Menu;
use App\ValueObject\OrderItem;

/**
 * Корзина в сессии: хранит только идентификаторы позиций и количество.
 *
 * Цена и название всегда берутся из каталога, чтобы клиент не мог
 * подменить стоимость заказа.
 */
final class Cart
{
    public function __construct(private readonly string $sessionKey = 'lab10_cart')
    {
        if (!isset($_SESSION[$this->sessionKey]) || !is_array($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }

    public function add(string $id, int $quantity = 1): void
    {
        $this->setQuantity($id, $this->quantityOf($id) + max(1, $quantity));
    }

    public function increase(string $id, int $delta = 1): void
    {
        $this->setQuantity($id, $this->quantityOf($id) + $delta);
    }

    public function decrease(string $id, int $delta = 1): void
    {
        $this->setQuantity($id, $this->quantityOf($id) - $delta);
    }

    public function remove(string $id): void
    {
        unset($_SESSION[$this->sessionKey][$id]);
    }

    public function clear(): void
    {
        $_SESSION[$this->sessionKey] = [];
    }

    public function quantityOf(string $id): int
    {
        return (int) ($_SESSION[$this->sessionKey][$id] ?? 0);
    }

    public function isEmpty(): bool
    {
        return $this->items() === [];
    }

    public function count(): int
    {
        return array_sum($this->items());
    }

    /** @return array<string, int> */
    public function items(): array
    {
        /** @var array<string, int> $items */
        $items = $_SESSION[$this->sessionKey];

        return array_filter($items, static fn (int $qty): bool => $qty > 0);
    }

    /** @return list<OrderItem> */
    public function lines(Menu $menu): array
    {
        $lines = [];

        foreach ($this->items() as $id => $quantity) {
            $product = $menu->find($id);

            if ($product === null) {
                continue;
            }

            $lines[] = new OrderItem(
                $product->id,
                $product->title,
                $product->price,
                $quantity,
            );
        }

        return $lines;
    }

    public function subtotal(Menu $menu): int
    {
        $sum = 0;

        foreach ($this->lines($menu) as $line) {
            $sum += $line->total();
        }

        return $sum;
    }

    private function setQuantity(string $id, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($id);

            return;
        }

        $_SESSION[$this->sessionKey][$id] = min(99, $quantity);
    }
}
