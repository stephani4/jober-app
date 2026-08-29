<?php

namespace App\Enums;

/**
 * Статус заказа: модерация, публикация, выполнение, завершение или отказ.
 */
enum OrderStatus: string
{
    case Moderate = 'moderate';
    case Wait = 'wait';
    case Process = 'process';
    case Complete = 'complete';
    case Cancel = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::Moderate => 'На модерации',
            self::Wait => 'Ожидает исполнителя',
            self::Process => 'Выполняется',
            self::Complete => 'Исполнено',
            self::Cancel => 'Отклонён',
        };
    }
}
