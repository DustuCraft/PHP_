<?php

declare(strict_types=1);

namespace App\Delivery;

use App\Enums\DeliveryMethod;
use App\Exceptions\AddressException;
use App\ValueObject\Order;
use App\ValueObject\PickupPoint;

/**
 * Самовывоз: доставка бесплатна, срок зависит от загрузки кухни и точки.
 */
final class Pickup extends AbstractDeliveryCalculator
{
    public function method(): DeliveryMethod
    {
        return DeliveryMethod::Pickup;
    }

    protected function validateRequest(Order $order): void
    {
        $point = $order->pickupPoint ?? throw AddressException::missingPickupPoint();

        $known = PickupPoint::find($point->code)
            ?? throw new AddressException(sprintf('Пункт «%s» не найден.', $point->code));

        if (!$known->isOpen) {
            throw new \RuntimeException(sprintf('Пункт «%s» закрыт.', $known->title));
        }
    }

    protected function deliveryFee(Order $order): int
    {
        return 0;
    }

    protected function estimatedMinutes(Order $order): int
    {
        return 15 + $order->count() * 2;
    }
}
