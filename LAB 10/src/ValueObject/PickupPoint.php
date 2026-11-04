<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * Пункт выдачи заказов для самовывоза.
 */
final readonly class PickupPoint
{
    public function __construct(
        public string $code,
        public string $title,
        public string $address,
        public string $hours,
        public bool $isOpen = true,
    ) {}

    /** @return list<self> */
    public static function all(): array
    {
        return [
            new self('PVZ-01', 'Пункт «Центр»', 'ул. Абая, 15', '09:00–21:00'),
            new self('PVZ-02', 'Пункт «Самал»', 'ул. Достык, 210', '10:00–22:00'),
            new self('PVZ-03', 'Пункт «Ауэзов»', 'пр. Алтынсарина, 24', '09:00–20:00'),
            new self('PVZ-04', 'Пункт «Вокзал»', 'ул. Конаева, 4', 'закрыт на ремонт', false),
        ];
    }

    public static function find(string $code): ?self
    {
        foreach (self::all() as $point) {
            if ($point->code === $code) {
                return $point;
            }
        }

        return null;
    }
}
