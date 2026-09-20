<?php

namespace App\Enums;

/**
 * Права персонала админки (Spatie, guard=admin).
 */
enum AdminPermission: string
{
    case OrdersView = 'orders.view';
    case OrdersApprove = 'orders.approve';
    case OrdersCancel = 'orders.cancel';
    case UsersView = 'users.view';
    case UsersEdit = 'users.edit';
    case UsersBlock = 'users.block';
    case AdminsView = 'admins.view';
    case AdminsCreate = 'admins.create';
    case AdminsUpdate = 'admins.update';
    case AdminsDelete = 'admins.delete';

    public function label(): string
    {
        return match ($this) {
            self::OrdersView => 'Просмотр заказов',
            self::OrdersApprove => 'Подтверждение заказов',
            self::OrdersCancel => 'Отмена заказов',
            self::UsersView => 'Просмотр пользователей',
            self::UsersEdit => 'Редактирование пользователей',
            self::UsersBlock => 'Блокировка пользователей',
            self::AdminsView => 'Просмотр администраторов',
            self::AdminsCreate => 'Создание администраторов',
            self::AdminsUpdate => 'Редактирование администраторов',
            self::AdminsDelete => 'Удаление администраторов',
        };
    }

    /**
     * Права на учётные записи сотрудников админки.
     */
    public function isStaffManagement(): bool
    {
        return str_starts_with($this->value, 'admins.');
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
