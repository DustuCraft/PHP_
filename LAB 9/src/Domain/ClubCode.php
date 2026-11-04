<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class ClubCode
{
    public function __construct(public string $value)
    {
        if (preg_match('/^[A-Z]{2,4}-\d{2}$/', $value) !== 1) {
            throw new \InvalidArgumentException(
                'Код клуба: 2–4 заглавные латинские буквы, дефис и две цифры (например, ACT-01).'
            );
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
