import { http } from '@/services/HttpClient'
import { orderTypeListSchema, type OrderTypeList } from '@/schemas/order'

/**
 * Справочник видов заказа в контуре /api/admin.
 */
export class AdminOrderTypeService {
  async list(): Promise<OrderTypeList> {
    const { data } = await http.client.get('/admin/order-types')
    return orderTypeListSchema.parse(data)
  }
}

export const adminOrderTypeService = new AdminOrderTypeService()