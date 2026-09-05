<?php

namespace App\Enums;

enum Reorder: string
{
    case NotUrgent = 'not_urgent';
    case LessUrgent = 'less_urgent';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::NotUrgent => 'Not Urgent',
            self::LessUrgent => 'Less Urgent',
            self::Urgent => 'Urgent',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function colour(): string
    {
        return match ($this) {
            self::NotUrgent => 'bg-gray-200',
            self::LessUrgent => 'bg-yellow-200',
            self::Urgent => 'bg-red-200',
        };
    }

    public static function getUrgency(?int $quantity = null): self
    {
        return match (true) {
            is_int($quantity) && $quantity < 5 => self::Urgent,
            is_int($quantity) && $quantity <= 7 => self::LessUrgent,
            default => self::NotUrgent
        };
    }
}
