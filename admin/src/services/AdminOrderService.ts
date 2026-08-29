import { http } from '@/services/HttpClient'
import {
  orderActionSchema,
  orderListSchema,
  type Order,
  type OrderList,
  type OrderStatus,
} from '@/schemas/order'

/**
 * Заказы в контуре /api/admin.
 */
export class AdminOrderService {
  async list(status: OrderStatus | 'all', cursor?: number | null): Promise<OrderList> {
    const { data } = await http.client.get('/admin/orders', {
      params: {
        status,
        cursor: cursor ?? undefined,
      },
    })
    return orderListSchema.parse(data)
  }

  async approve(orderId: number): Promise<Order> {
    const { data } = await http.client.post(`/admin/orders/${orderId}/approve`)
    return orderActionSchema.parse(data).order
  }

  async cancel(orderId: number, reason: string): Promise<Order> {
    const { data } = await http.client.post(`/admin/orders/${orderId}/cancel`, { reason })
    return orderActionSchema.parse(data).order
  }
}

export const adminOrderService = new AdminOrderService()
