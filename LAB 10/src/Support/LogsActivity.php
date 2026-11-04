<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Небольшое независимое поведение: накопление записей журнала.
 *
 * Trait не создаёт иерархию и не хранит бизнес-модель — только
 * повторно используемый механизм логирования.
 */
trait LogsActivity
{
    /** @var list<string> */
    private array $log = [];

    final protected function log(string $message): void
    {
        $this->log[] = (new \DateTimeImmutable())->format('H:i:s') . ' ' . $message;
    }

    /** @return list<string> */
    final public function logEntries(): array
    {
        return $this->log;
    }
}
