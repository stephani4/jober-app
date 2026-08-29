<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Executor = 'executor';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Заказчик',
            self::Executor => 'Исполнитель',
        };
    }
}
