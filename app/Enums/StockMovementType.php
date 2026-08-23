<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Sale = 'sale';
    case Restock = 'restock';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Sale',
            self::Restock => 'Restock',
            self::Adjustment => 'Adjustment',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
