<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\DeliveryMethod;

/**
 * Сбой выполнения операции: нет свободных курьеров, пункт выдачи закрыт,
 * калькулятор для способа получения не подключён.
 *
 * Свойство previous сохраняет исходную причину, чтобы журнал не терял контекст.
 */
final class DeliveryException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly DeliveryMethod $method,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
