import { z } from 'zod'
import { http } from '@/services/HttpClient'
import { orderTypeSchema, type OrderType } from '@/schemas/order'

const orderTypeListSchema = z.object({
  types: z.array(orderTypeSchema),
})

/**
 * Справочник видов заказа.
 */
export class OrderTypeService {
  async list(): Promise<OrderType[]> {
    const { data } = await http.client.get('/order-types')
    return orderTypeListSchema.parse(data).types
  }
}

export const orderTypeService = new OrderTypeService()
