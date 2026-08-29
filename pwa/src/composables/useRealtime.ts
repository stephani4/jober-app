import { watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { realtimeService } from '@/services/RealtimeService'
import { restoreActiveOrder, resetActiveOrderRestore } from '@/composables/useRestoreActiveOrder'
import { useOrdersStore } from '@/stores/orders'
import { useOrderExecuteStore } from '@/stores/orderExecute'
import { useOrderOfferStore } from '@/stores/orderOffer'
import { useNotificationsStore } from '@/stores/notifications'
import { useOrderChatStore } from '@/stores/orderChat'
import { useOrderHistoryStore } from '@/stores/orderHistory'
import { useRealtimeStore } from '@/stores/realtime'
import { useSearchOrdersStore } from '@/stores/searchOrders'

let started = false
let stopEvents: (() => void) | null = null

/**
 * Подключает Centrifugo после авторизации и разводит realtime-события по сторам.
 */
export function useRealtime() {
  const auth = useAuth()
  const router = useRouter()
  const toast = useToast()
  const realtime = useRealtimeStore()
  const orders = useOrdersStore()
  const search = useSearchOrdersStore()
  const offer = useOrderOfferStore()
  const execute = useOrderExecuteStore()
  const notifications = useNotificationsStore()
  const chat = useOrderChatStore()
  const history = useOrderHistoryStore()
  const { status } = storeToRefs(realtime)

  if (!started) {
    started = true

    watch(
      () => auth.isAuthenticated.value,
      async (authenticated) => {
        if (authenticated) {
          stopEvents?.()
          const stopOrders = realtimeService.onOrderCreated((order) => {
            search.upsert(order)
            if (order.user_id === auth.user.value?.id) {
              orders.upsert(order)
            }

            const busyExecuting =
              router.currentRoute.value.name === 'order-execute' ||
              execute.executing?.status === 'process'

            if (
              auth.user.value?.role === 'executor' &&
              order.user_id !== auth.user.value.id &&
              order.status === 'wait' &&
              !busyExecuting
            ) {
              offer.present(order)
            }
          })
          const stopTaken = realtimeService.onOrderTaken((orderId) => {
            search.remove(orderId)
            offer.dismissIf(orderId)
          })
          const stopStatus = realtimeService.onOrderStatus((event) => {
            if (event.order.status !== 'wait') {
              search.remove(event.order.id)
              offer.dismissIf(event.order.id)
            }
            if (event.order.user_id === auth.user.value?.id) {
              orders.upsert(event.order)
              history.ingest(event.order)
            }
          })
          const stopModerated = realtimeService.onOrderModerated((event) => {
            if (event.order.user_id !== auth.user.value?.id) {
              return
            }
            orders.upsert(event.order)
            history.ingest(event.order)
            if (event.order.status === 'cancel') {
              toast.add({
                severity: 'error',
                summary: 'Заказ отклонён',
                detail: event.order.reason ?? 'Заказ не прошёл модерацию.',
                life: 6000,
              })
              return
            }
            toast.add({
              severity: 'success',
              summary: 'Заказ опубликован',
              detail: 'Модерация пройдена, заказ доступен исполнителям.',
              life: 4500,
            })
          })
          const stopNotifications = realtimeService.onNotificationCreated((event) => {
            notifications.ingestCreated(event)
            toast.add({
              severity: 'success',
              summary: event.notification.title,
              detail: event.notification.body,
              life: 4500,
            })
          })
          stopEvents = () => {
            stopOrders()
            stopTaken()
            stopStatus()
            stopModerated()
            stopNotifications()
          }
          try {
            await realtime.connect()
            void notifications.fetchUnreadCount()
            await restoreActiveOrder(router, auth.user.value?.id ?? null)
          } catch {
            // соединение переустановится при следующем bootstrap/логине
          }
          return
        }

        stopEvents?.()
        stopEvents = null
        resetActiveOrderRestore()
        realtime.disconnect()
        orders.reset()
        search.reset()
        offer.reset()
        execute.reset()
        notifications.reset()
        chat.reset()
        history.reset()
      },
      { immediate: true },
    )
  }

  return {
    status,
    connect: () => realtime.connect(),
    disconnect: () => realtime.disconnect(),
  }
}
