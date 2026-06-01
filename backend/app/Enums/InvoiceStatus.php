<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Disputed = 'disputed';
    case WriteOff = 'write_off';

    /**
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::Draft->value => [self::Pending->value, self::Cancelled->value],
            self::Pending->value => [self::Partial->value, self::Paid->value, self::Overdue->value, self::Cancelled->value, self::Disputed->value, self::WriteOff->value],
            self::Partial->value => [self::Paid->value, self::Overdue->value, self::Cancelled->value, self::Disputed->value, self::WriteOff->value],
            self::Overdue->value => [self::Partial->value, self::Paid->value, self::Disputed->value, self::WriteOff->value],
            self::Disputed->value => [self::Pending->value, self::Partial->value, self::Cancelled->value, self::WriteOff->value],
            self::Paid->value => [self::Refunded->value],
            self::Cancelled->value => [],
            self::Refunded->value => [],
            self::WriteOff->value => [],
        ];
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status->value, self::transitions()[$this->value] ?? [], true);
    }
}
