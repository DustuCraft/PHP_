<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Enums\DeliveryMethod;
use App\Support\Money;

/**
 * Результат расчёта доставки: способ, стоимость, срок и итог заказа.
 */
final readonly class DeliveryQuote
{
    public function __construct(
        public DeliveryMethod $method,
        public int $fee,
        public int $minutes,
        public int $total,
        public string $message = '',
    ) {}

    public function isFree(): bool
    {
        return $this->fee === 0;
    }

    public function feeLabel(): string
    {
        return $this->isFree() ? 'бесплатно' : Money::format($this->fee);
    }

    public function eta(): string
    {
        $spread = $this->method === DeliveryMethod::Courier ? 10 : 5;

        return sprintf('%d–%d мин', $this->minutes, $this->minutes + $spread);
    }

    public function totalLabel(): string
    {
        return Money::format($this->total);
    }
}
