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
    case Cancel = 'cancel';
    case Confirmation = 'confirmation';

    public function label(): string
    {
        return match ($this) {
            self::Confirmation => 'Подтверждение выполнения',
            self::Wait => 'Ожидает исполнителя',
            self::Process => 'Выполняется',
            self::Complete => 'Исполнено',
            self::Cancel => 'Отменен',
        };
    }
}
