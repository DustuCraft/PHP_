<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\DeliveryMethod;
use App\ValueObject\DeliveryQuote;
use App\ValueObject\Order;

/**
 * Контракт калькулятора доставки.
 *
 * Любой способ получения заказа (курьер, самовывоз, будущий постамат)
 * реализует этот интерфейс, поэтому DeliveryService не зависит
 * от конкретных классов.
 */
interface DeliveryCalculator
{
    /**
     * Способ получения, который обслуживает калькулятор.
     */
    public function method(): DeliveryMethod;

    /**
     * Стоимость доставки, срок и итоговая сумма заказа.
     *
     * @throws \App\Exceptions\AddressException  некорректный адрес или пункт выдачи
     * @throws \App\Exceptions\DeliveryException сбой при расчёте доставки
     */
    public function calculate(Order $order): DeliveryQuote;
}
