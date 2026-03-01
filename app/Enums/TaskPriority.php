<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low    = 'low';
    case Medium = 'medium';
    case High   = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Aşağı',
            self::Medium => 'Orta',
            self::High   => 'Yüksək',
            self::Urgent => 'Təcili',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low    => 'green',
            self::Medium => 'blue',
            self::High   => 'orange',
            self::Urgent => 'red',
        };
    }
}
