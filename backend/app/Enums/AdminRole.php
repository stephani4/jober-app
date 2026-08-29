<?php

namespace App\Enums;

/**
 * Роли персонала админки (Spatie, guard=admin).
 */
enum AdminRole: string
{
    case SuperAdmin = 'super-admin';
    case Moderator = 'moderator';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Суперадмин',
            self::Moderator => 'Модератор',
        };
    }
}
