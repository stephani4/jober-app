import { watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useOrderSound } from '@/composables/useOrderSound'
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
  const sound = useOrderSound()
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
            if (event.order.status === 'wait') {
              search.upsert(event.order)
            } else {
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
          const stopCancelled = realtimeService.onOrderCancelled((event) => {
            if (event.order) {
              // Отменённый заказ вычищается из любых списков (в т.ч. /orders получателя).
              orders.upsert(event.order)
            }

            const route = router.currentRoute.value
            const onExecuteScreen =
              route.name === 'order-execute' &&
              Number(route.params.orderId) === event.order_id

            if (onExecuteScreen || execute.executing?.order_id === event.order_id) {
              execute.reset()
              if (onExecuteScreen) {
                void router.replace({ name: 'orders' })
              }
            }

            toast.add({
              severity: 'error',
              summary: 'Заказ отменён',
              detail: 'Заказчик отменил заказ. Выполнение остановлено.',
              life: 6000,
            })
          })
          const stopDeclined = realtimeService.onOrderDeclined((event) => {
            const route = router.currentRoute.value
            const onWatchingScreen =
              route.name === 'order-watching' &&
              Number(route.params.orderId) === event.order_id

            if (onWatchingScreen || execute.executing?.order_id === event.order_id) {
              execute.reset()
              if (onWatchingScreen) {
                void router.replace({ name: 'orders' })
              }
            }

            toast.add({
              severity: 'warn',
              summary: 'Исполнитель отказался',
              detail: 'Заказ вернулся в поиск и снова доступен исполнителям.',
              life: 6000,
            })
          })
          const stopNotifications = realtimeService.onNotificationCreated((event) => {
            notifications.ingestCreated(event)
            if (event.notification.type === 'order.taken') {
              // Заказчику: исполнитель откликнулся и взял его заказ в работу.
              // Уведомление этого типа приходит только автору заказа.
              void sound.playOrderTaken()
            }
            if (event.notification.type === 'order.cancelled' || event.notification.type === 'order.declined') {
              // Тост уже показывается в обработчиках order.cancelled / order.declined
              return
            }
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
            stopCancelled()
            stopDeclined()
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
