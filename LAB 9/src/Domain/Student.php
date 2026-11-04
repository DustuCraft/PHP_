<?php

declare(strict_types=1);

namespace App\Domain;

final class Student extends User
{
    public function __construct(
        int $id,
        string $fullName,
        string $email,
        public readonly string $program,
        public readonly string $group,
    ) {
        parent::__construct($id, $fullName, $email);

        if (trim($program) === '' || trim($group) === '') {
            throw new \InvalidArgumentException('Программа и группа обязательны.');
        }
    }

    #[\Override]
    public function roleLabel(): string
    {
        return sprintf('Студент %s (%s)', $this->group, $this->program);
    }
}
