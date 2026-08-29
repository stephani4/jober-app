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
}
