/**
 * Права админки. Значения совпадают с backend App\Enums\AdminPermission.
 */
export const Permission = {
  OrdersView: 'orders.view',
  OrdersApprove: 'orders.approve',
  OrdersCancel: 'orders.cancel',
  UsersView: 'users.view',
  UsersEdit: 'users.edit',
  UsersBlock: 'users.block',
  AdminsView: 'admins.view',
  AdminsCreate: 'admins.create',
  AdminsUpdate: 'admins.update',
  AdminsDelete: 'admins.delete',
} as const

export type Permission = (typeof Permission)[keyof typeof Permission]
