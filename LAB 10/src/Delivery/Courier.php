<?php

declare(strict_types=1);

namespace App\Delivery;

use App\Enums\DeliveryMethod;
use App\Exceptions\AddressException;
use App\ValueObject\Order;

/**
 * Доставка курьером: стоимость зависит от района и суммы заказа.
 */
final class Courier extends AbstractDeliveryCalculator
{
    /** Средняя удалённость районов от кухни, км. */
    private const DISTRICTS = [
        'Центр' => 2.0,
        'Бостандык' => 5.2,
        'Ауэзов' => 7.4,
        'Медеу' => 9.0,
        'Алатау' => 11.5,
    ];

    private const BASE_FEE = 600;

    private const PER_KM = 120;

    /** Заказов, которые один курьер уже не увезёт. */
    private const MAX_ITEMS = 12;

    /** @return list<string> */
    public static function districts(): array
    {
        return array_keys(self::DISTRICTS);
    }

    public function method(): DeliveryMethod
    {
        return DeliveryMethod::Courier;
    }

    protected function validateRequest(Order $order): void
    {
        $address = $order->address ?? throw AddressException::missingAddress();

        if (!isset(self::DISTRICTS[$address->district])) {
            throw AddressException::unknownDistrict($address->district);
        }

        if (preg_match('/^[\p{L}][\p{L}\s.\-]{2,}$/u', trim($address->street)) !== 1) {
            throw AddressException::badStreet($address->street);
        }

        if (preg_match('/^\d{1,4}[\p{L}]?$/u', trim($address->house)) !== 1) {
            throw AddressException::badHouse($address->house);
        }

        if ($order->count() > self::MAX_ITEMS) {
            throw new \RuntimeException('Все курьеры заняты: заказ слишком большой.');
        }
    }

    protected function deliveryFee(Order $order): int
    {
        $distance = self::DISTRICTS[$order->address->district];

        if ($this->subtotal($order) >= self::FREE_FROM) {
            return 0;
        }

        $extraKm = max(0, (int) ceil($distance - 3.0));

        return self::BASE_FEE + $extraKm * self::PER_KM;
    }

    protected function estimatedMinutes(Order $order): int
    {
        $distance = self::DISTRICTS[$order->address->district];

        return 20 + (int) round($distance * 3) + min(20, $order->count() * 2);
    }
}
