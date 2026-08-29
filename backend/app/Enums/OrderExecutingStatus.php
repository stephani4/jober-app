<?php

namespace App\Enums;

/**
 * Статус выполнения заказа или точки заказа.
 */
enum OrderExecutingStatus: string
{
    case Wait = 'wait';
    case Process = 'process';
    case Complete = 'complete';

    public function label(): string
    {
        return match ($this) {
            self::Wait => 'Ожидает исполнителя',
            self::Process => 'Выполняется',
            self::Complete => 'Исполнено',
        };
    }
}
