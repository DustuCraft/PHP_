<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Ошибка входных данных: адрес доставки или пункт выдачи указаны неверно.
 * Наследуется от InvalidArgumentException, чтобы граница сценария могла
 * ловить как конкретный тип, так и все ошибки аргументов.
 */
final class AddressException extends \InvalidArgumentException
{
    public static function missingAddress(): self
    {
        return new self('Для доставки курьером нужен адрес.');
    }

    public static function unknownDistrict(string $district): self
    {
        return new self(sprintf('Район «%s» не обслуживается.', $district));
    }

    public static function badHouse(string $house): self
    {
        return new self(sprintf('Номер дома «%s» указан некорректно.', $house));
    }

    public static function badStreet(string $street): self
    {
        return new self(sprintf('Улица «%s» указана некорректно.', $street));
    }

    public static function missingPickupPoint(): self
    {
        return new self('Выберите пункт выдачи из списка.');
    }
}
