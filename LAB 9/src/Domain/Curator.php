<?php

declare(strict_types=1);

namespace App\Domain;

final class Curator extends User
{
    public function __construct(
        int $id,
        string $fullName,
        string $email,
        public readonly string $department,
    ) {
        parent::__construct($id, $fullName, $email);

        if (trim($department) === '') {
            throw new \InvalidArgumentException('Кафедра куратора обязательна.');
        }
    }

    #[\Override]
    public function roleLabel(): string
    {
        return 'Куратор, ' . $this->department;
    }
}
