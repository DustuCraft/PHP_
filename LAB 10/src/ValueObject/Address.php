<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * Адрес доставки курьером.
 */
final readonly class Address
{
    public function __construct(
        public string $city,
        public string $district,
        public string $street,
        public string $house,
        public string $apartment = '',
        public string $comment = '',
    ) {}

    public function line(): string
    {
        $parts = [
            sprintf('г. %s', $this->city),
            sprintf('ул. %s', $this->street),
            sprintf('д. %s', $this->house),
        ];

        if (trim($this->apartment) !== '') {
            $parts[] = sprintf('кв. %s', $this->apartment);
        }

        return implode(', ', $parts);
    }
}
