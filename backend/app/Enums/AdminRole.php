<?php

namespace App\Enums;

/**
 * Роли персонала админки (Spatie, guard=admin).
 */
enum AdminRole: string
{
    case SuperAdmin = 'super-admin';
    case Moderator = 'moderator';
    case Users = 'users';
    case AdminWorker = 'admin-worker';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Суперадмин',
            self::Moderator => 'Модератор',
            self::Users => 'Пользователи',
            self::AdminWorker => 'Работа с администраторами',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
