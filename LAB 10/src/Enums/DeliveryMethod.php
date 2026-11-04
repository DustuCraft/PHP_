<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Способ получения заказа.
 */
enum DeliveryMethod: string
{
    case Courier = 'courier';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Courier => 'Доставка курьером',
            self::Pickup => 'Самовывоз',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Courier => 'Курьер привезёт заказ к подъезду',
            self::Pickup => 'Заберёте заказ из пункта выдачи',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Courier => 'assets/courier-scooter.png',
            self::Pickup => 'assets/courier-walk.png',
        };
    }
}
