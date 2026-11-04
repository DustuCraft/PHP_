<?php

declare(strict_types=1);

namespace App\Delivery;

use App\Contracts\DeliveryCalculator;
use App\Exceptions\AddressException;
use App\Exceptions\DeliveryException;
use App\Support\LogsActivity;
use App\Support\Money;
use App\ValueObject\DeliveryQuote;
use App\ValueObject\Order;

/**
 * Общий алгоритм расчёта доставки.
 *
 * Шаблонный метод calculate() фиксирует порядок шагов, подклассы
 * переопределяют только проверку входных данных, стоимость и срок.
 */
abstract class AbstractDeliveryCalculator implements DeliveryCalculator
{
    use LogsActivity;

    protected const FREE_FROM = 8000;

    final public function calculate(Order $order): DeliveryQuote
    {
        try {
            $this->validateRequest($order);
            $fee = $this->deliveryFee($order);
            $minutes = $this->estimatedMinutes($order);
        } catch (AddressException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DeliveryException(
                $e->getMessage() !== '' ? $e->getMessage() : 'Не удалось рассчитать доставку.',
                $this->method(),
                $e
            );
        }

        $total = $order->subtotal() + $fee;

        $this->log(sprintf(
            '%s: %s, срок %d мин, итого %s',
            $this->method()->label(),
            $fee === 0 ? 'бесплатно' : Money::format($fee),
            $minutes,
            Money::format($total)
        ));

        return new DeliveryQuote(
            method: $this->method(),
            fee: $fee,
            minutes: $minutes,
            total: $total,
        );
    }

    /**
     * Проверяет адрес или пункт выдачи. Некорректные данные — AddressException.
     *
     * @throws AddressException
     */
    abstract protected function validateRequest(Order $order): void;

    /**
     * Стоимость доставки в тенге. 0 — бесплатно.
     */
    abstract protected function deliveryFee(Order $order): int;

    /**
     * Ожидаемый срок получения в минутах.
     */
    abstract protected function estimatedMinutes(Order $order): int;

    protected function subtotal(Order $order): int
    {
        return $order->subtotal();
    }
}
