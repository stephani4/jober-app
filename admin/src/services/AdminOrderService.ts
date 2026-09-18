import { http } from '@/services/HttpClient'
import {
  orderActionSchema,
  orderExecutingPayloadSchema,
  orderListSchema,
  orderShowSchema,
  orderTypeListSchema,
  realtimeTokenSchema,
  type Order,
  type OrderExecuting,
  type OrderList,
  type OrderStatus,
  type OrderTypeList,
} from '@/schemas/order'

/** Значения фильтров списка заказов (числа приходят из PrimeVue InputNumber). */
export interface OrderListFilters {
  status: OrderStatus | 'all'
  id?: number
  order_type_id?: number
  cost_min?: number
  cost_max?: number
}

/**
 * Заказы в контуре /api/admin.
 */
export class AdminOrderService {
  async list(filters: OrderListFilters, cursor?: number | null): Promise<OrderList> {
    const { data } = await http.client.get('/admin/orders', {
      params: {
        status: filters.status,
        id: filters.id ?? undefined,
        order_type_id: filters.order_type_id ?? undefined,
        cost_min: filters.cost_min ?? undefined,
        cost_max: filters.cost_max ?? undefined,
        cursor: cursor ?? undefined,
      },
    })
    return orderListSchema.parse(data)
  }

  /**
   * Страница заказа: общая информация, точки и исполнитель.
   */
  async show(orderId: number): Promise<Order> {
    const { data } = await http.client.get(`/admin/orders/${orderId}`)
    return orderShowSchema.parse(data).order
  }

  /**
   * Текущее выполнение заказа для вкладки «Наблюдение» (null, пока не взято в работу).
   */
  async executing(orderId: number): Promise<OrderExecuting | null> {
    const { data } = await http.client.get(`/admin/orders/${orderId}/executing`)
    return orderExecutingPayloadSchema.parse(data).executing
  }

  /**
   * Connection JWT Centrifugo; с orderId — подписка на канал наблюдения заказа.
   */
  async realtimeToken(orderId: number): Promise<string> {
    const { data } = await http.client.get('/admin/realtime/token', {
      params: { order_id: orderId },
    })
    return realtimeTokenSchema.parse(data).token
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
