<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo               = 'todo';
    case InProgress         = 'in_progress';
    case WaitingForApprove  = 'waiting_for_approve';
    case Completed          = 'completed';
    case Canceled           = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Todo              => 'Görüləcək',
            self::InProgress        => 'İcra olunur',
            self::WaitingForApprove => 'Təsdiq gözləyir',
            self::Completed         => 'Tamamlandı',
            self::Canceled          => 'Ləğv olundu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Todo              => 'gray',
            self::InProgress        => 'blue',
            self::WaitingForApprove => 'yellow',
            self::Completed         => 'green',
            self::Canceled          => 'red',
        };
    }

    /**
     * Statusdan "Done" seçildikdə require_approval aktiv isə
     * "waiting_for_approve"-a keçməli, yoxsa birbaşa "completed"
     */
    public static function resolveNextStatus(string $requested, bool $requireApproval): self
    {
        if ($requested === self::Completed->value && $requireApproval) {
            return self::WaitingForApprove;
        }
        return self::from($requested);
    }

    /**
     * Cari statusdan hansı statuslara keçmək olar
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Todo              => [self::InProgress, self::Canceled],
            self::InProgress        => [self::WaitingForApprove, self::Completed, self::Canceled],
            self::WaitingForApprove => [self::Completed, self::InProgress, self::Canceled],
            self::Completed         => [],
            self::Canceled          => [self::Todo],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions());
    }
}
