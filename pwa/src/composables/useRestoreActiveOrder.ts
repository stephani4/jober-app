import type { Router } from 'vue-router'
import { orderService } from '@/services/OrderService'

let restoredForUserId: number | null = null
let lastRestored = false
let inflight: Promise<boolean> | null = null

/**
 * После входа или перезагрузки открывает активное выполнение, если оно есть.
 * @returns true, если пользователя отправили (или он уже) на экран выполнения.
 */
export function restoreActiveOrder(router: Router, userId: number | null): Promise<boolean> {
  if (!userId) {
    return Promise.resolve(false)
  }
  if (inflight) {
    return inflight
  }
  if (restoredForUserId === userId) {
    return Promise.resolve(lastRestored)
  }

  inflight = (async () => {
    try {
      const active = await orderService.active()
      restoredForUserId = userId
      if (!active.order_id || !active.view) {
        lastRestored = false
        return false
      }

      const name = active.view === 'execute' ? 'order-execute' : 'order-watching'
      const current = router.currentRoute.value
      if (current.name !== name || Number(current.params.orderId) !== active.order_id) {
        await router.replace({ name, params: { orderId: String(active.order_id) } })
      }
      lastRestored = true
      return true
    } catch {
      restoredForUserId = null
      lastRestored = false
      return false
    } finally {
      inflight = null
    }
  })()

  return inflight
}

export function resetActiveOrderRestore(): void {
  restoredForUserId = null
  lastRestored = false
  inflight = null
}
