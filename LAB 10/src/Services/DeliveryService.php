<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DeliveryCalculator;
use App\Enums\DeliveryMethod;
use App\Exceptions\DeliveryException;
use App\ValueObject\DeliveryQuote;
use App\ValueObject\Order;

/**
 * Сервис расчёта доставки.
 *
 * Зависит от интерфейса DeliveryCalculator, поэтому новый способ получения
 * (например, постамат) добавляется отдельным классом и передачей в массив.
 */
final class DeliveryService
{
    /** @param list<DeliveryCalculator> $calculators */
    public function __construct(private readonly array $calculators) {}

    public function quote(Order $order): DeliveryQuote
    {
        return $this->calculatorFor($order->method)->calculate($order);
    }

    public function calculatorFor(DeliveryMethod $method): DeliveryCalculator
    {
        foreach ($this->calculators as $calculator) {
            if ($calculator->method() === $method) {
                return $calculator;
            }
        }

        throw new DeliveryException(
            sprintf('Способ получения «%s» не обслуживается.', $method->label()),
            $method
        );
    }

    /** @return list<DeliveryMethod> */
    public function supportedMethods(): array
    {
        return array_map(
            static fn (DeliveryCalculator $calculator): DeliveryMethod => $calculator->method(),
            $this->calculators
        );
    }
}
