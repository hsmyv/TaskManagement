<?php

namespace App\Enums;

/**
 * Qlobal sistem rolları (Spatie Permission ilə idarə olunur)
 */
enum UserRole: string
{
    case Administrator     = 'administrator';
    case ExecutiveManager  = 'executive_manager';
    case SeniorManager     = 'senior_manager';
    case MiddleManager     = 'middle_manager';
    case Employee          = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Administrator    => 'Administrator',
            self::ExecutiveManager => 'İdarə heyəti üzvü',
            self::SeniorManager    => 'Baş menecer',
            self::MiddleManager    => 'Menecer',
            self::Employee         => 'Əməkdaş',
        };
    }

    /**
     * Bu rolu daşıyanın qlobal səlahiyyəti varmı?
     */
    public function hasGlobalAccess(): bool
    {
        return in_array($this, [self::Administrator, self::ExecutiveManager]);
    }

    /**
     * Space yarada bilərmi?
     */
    public function canManageSpaces(): bool
    {
        return $this === self::Administrator;
    }
}
