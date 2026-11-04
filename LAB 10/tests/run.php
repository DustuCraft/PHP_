<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Catalog\Menu;
use App\Delivery\Courier;
use App\Delivery\Pickup;
use App\Enums\DeliveryMethod;
use App\Exceptions\AddressException;
use App\Exceptions\DeliveryException;
use App\Services\DeliveryService;
use App\ValueObject\Address;
use App\ValueObject\Order;
use App\ValueObject\OrderItem as CartLine; // псевдоним для читаемости в тестах
use App\ValueObject\PickupPoint;

$passed = 0;
$failed = 0;

function check(string $label, bool $condition, string $details = ''): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "OK   {$label}\n";

        return;
    }

    $failed++;
    echo "FAIL {$label}" . ($details !== '' ? " — {$details}" : '') . "\n";
}

/** @param array<string, int> $lines */
function makeOrder(array $lines, DeliveryMethod $method, ?Address $address = null, ?PickupPoint $point = null): Order
{
    $menu = new Menu();
    $items = [];

    foreach ($lines as $id => $quantity) {
        $product = $menu->find($id);

        if ($product === null) {
            throw new RuntimeException("В меню нет позиции {$id}");
        }

        $items[] = new CartLine($product->id, $product->title, $product->price, $quantity);
    }

    return new Order($items, $method, $address, $point);
}

function throws(string $expected, callable $action): bool
{
    try {
        $action();
    } catch (Throwable $e) {
        return $e instanceof $expected;
    }

    return false;
}

$courier = new Courier();
$pickup = new Pickup();
$service = new DeliveryService([$courier, $pickup]);

$center = new Address('Алматы', 'Центр', 'Абая', '15', '12');
$aujezov = new Address('Алматы', 'Ауэзов', 'Алтынсарина', '24', '3');
$point = PickupPoint::find('PVZ-02');
$closedPoint = PickupPoint::find('PVZ-04');

echo "Тесты лабораторной работы №10 (вариант 4) — доставка\n";
echo str_repeat('-', 58) . "\n";

// 1. Курьер: платная доставка в центр города
$quote = $service->quote(makeOrder(['burger-cheese' => 1], DeliveryMethod::Courier, $center));
check('Курьер, Центр: доставка 600 ₸', $quote->fee === 600, "получено {$quote->fee}");
check('Курьер, Центр: итого 2 290 ₸', $quote->total === 2290, "получено {$quote->total}");
check('Курьер: срок 28 мин', $quote->minutes === 28, "получено {$quote->minutes}");

// 2. Курьер: бесплатная доставка от 8 000 ₸
$free = $service->quote(makeOrder(['combo-family' => 2], DeliveryMethod::Courier, $center));
check('Курьер: бесплатно от 8 000 ₸', $free->isFree(), "доставка {$free->fee}");
check('Курьер: итог равен сумме товаров', $free->total === 9980, "получено {$free->total}");

// 3. Курьер: дальний район дороже
$far = $service->quote(makeOrder(['burger-cheese' => 1], DeliveryMethod::Courier, $aujezov));
check('Курьер, Ауэзов: доставка 1 200 ₸', $far->fee === 1200, "получено {$far->fee}");

// 4. Самовывоз: бесплатно и быстрее курьера
$self = $service->quote(makeOrder(['burger-cheese' => 1, 'cola' => 1], DeliveryMethod::Pickup, null, $point));
check('Самовывоз: доставка 0 ₸', $self->fee === 0);
check('Самовывоз: срок 19 мин', $self->minutes === 19, "получено {$self->minutes}");
check('Самовывоз: способ в ответе', $self->method === DeliveryMethod::Pickup);

// 5. Ошибка входных данных: адрес не передан
check(
    'Курьер без адреса → AddressException',
    throws(AddressException::class, fn () => $service->quote(
        makeOrder(['burger-cheese' => 1], DeliveryMethod::Courier)
    ))
);

// 6. Ошибка входных данных: неверный номер дома
check(
    'Дом «abc» → AddressException',
    throws(AddressException::class, fn () => $service->quote(
        makeOrder(['burger-cheese' => 1], DeliveryMethod::Courier, new Address('Алматы', 'Центр', 'Абая', 'abc'))
    ))
);

// 7. Сбой операции: закрытый пункт выдачи, причина сохранена в previous
try {
    $service->quote(makeOrder(['cola' => 1], DeliveryMethod::Pickup, null, $closedPoint));
    check('Закрытый пункт → DeliveryException', false);
} catch (DeliveryException $e) {
    check('Закрытый пункт → DeliveryException', true);
    check('previous сохранён', $e->getPrevious() instanceof \RuntimeException);
    check('сообщение содержит название пункта', str_contains($e->getMessage(), 'закрыт'));
}

// 8. Сбой операции: курьеров не хватает на большой заказ
check(
    'Заказ на 13 позиций → DeliveryException',
    throws(DeliveryException::class, fn () => $service->quote(
        makeOrder(['burger-cheese' => 13], DeliveryMethod::Courier, $center)
    ))
);

// 9. Пустая корзина — ошибка аргументов
check(
    'Пустая корзина → InvalidArgumentException',
    throws(InvalidArgumentException::class, fn () => new Order([], DeliveryMethod::Courier, $center))
);

// 10. Trait LogsActivity: запись появляется после успешного расчёта
$logs = $courier->logEntries();
check('Журнал курьера не пуст', $logs !== []);
check('В журнале есть запись о доставке', str_contains($logs[0] ?? '', 'Доставка курьером'));

// 11. finally выполняется и при ошибке
$finished = false;

try {
    $service->quote(makeOrder(['burger-cheese' => 1], DeliveryMethod::Courier, new Address('Алматы', 'Центр', 'Абая', '')));
} catch (AddressException) {
    // ожидаемо
} finally {
    $finished = true;
}

check('finally выполнен после ошибки', $finished);

// 12. Полиморфизм: сервис работает через интерфейс, а не через классы
check('Courier реализует DeliveryCalculator', $courier instanceof \App\Contracts\DeliveryCalculator);
check('Pickup реализует DeliveryCalculator', $pickup instanceof \App\Contracts\DeliveryCalculator);
check(
    'Сервис без калькулятора самовывоза → DeliveryException',
    throws(DeliveryException::class, fn () => (new DeliveryService([$courier]))->quote(
        makeOrder(['cola' => 1], DeliveryMethod::Pickup, null, $point)
    ))
);

echo str_repeat('-', 58) . "\n";
echo sprintf("Пройдено: %d, провалено: %d\n", $passed, $failed);

exit($failed === 0 ? 0 : 1);
