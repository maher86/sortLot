<?php

namespace App\Enums;

enum PackageStatus: string
{
    case InTransit = 'in_transit';
    case AtPort = 'at_port';
    case InCustoms = 'in_customs';
    case InWarehouse = 'in_warehouse';
    case Sorting = 'sorting';
    case Sorted = 'sorted';
    case PartiallyShipped = 'partially_shipped';
    case Shipped = 'shipped';
    case Closed = 'closed';

    public function canTransitionTo(self $target): bool
    {
        if ($this === self::Sorted && $target === self::Sorting) {
            return true;
        }

        $order = array_column(self::cases(), 'value');

        return array_search($target->value, $order, true) > array_search($this->value, $order, true);
    }
}
