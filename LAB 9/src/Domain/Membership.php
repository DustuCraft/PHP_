<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Membership
{
    public function __construct(
        public int $studentId,
        public ClubCode $clubCode,
        public \DateTimeImmutable $joinedAt,
    ) {
        if ($studentId < 1) {
            throw new \InvalidArgumentException('Некорректный ID студента.');
        }

        if ($joinedAt > new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('Дата вступления не может быть в будущем.');
        }
    }
}
