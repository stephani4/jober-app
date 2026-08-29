import { defineStore } from 'pinia'
import { ref } from 'vue'
import { orderMessageService } from '@/services/OrderMessageService'
import type { OrderMessage } from '@/schemas/orderMessage'

export const useOrderChatStore = defineStore('orderChat', () => {
  const open = ref(false)
  const orderId = ref<number | null>(null)
  const messages = ref<OrderMessage[]>([])
  const draft = ref('')
  const loading = ref(false)
  const sending = ref(false)
  const error = ref('')
  const unreadCount = ref(0)
  const loaded = ref(false)

  function toggle(): void {
    open.value = !open.value
    if (open.value) {
      unreadCount.value = 0
    }
  }

  function close(): void {
    open.value = false
  }

  /**
   * Привязывает стор к заказу, не закрывая уже открытую панель.
   */
  function bindOrder(id: number | null): void {
    if (orderId.value === id) {
      return
    }
    orderId.value = id
    messages.value = []
    draft.value = ''
    error.value = ''
    loaded.value = false
    unreadCount.value = 0
    loading.value = false
    sending.value = false
  }

  /**
   * Добавляет входящее сообщение, если его ещё нет в ленте.
   */
  function ingest(message: OrderMessage, viewerId: number | null): void {
    if (orderId.value != null && message.order_id !== orderId.value) {
      return
    }
    if (messages.value.some((item) => item.id === message.id)) {
      return
    }
    messages.value = [...messages.value, message]
    if (!open.value && viewerId != null && message.user_id !== viewerId) {
      unreadCount.value += 1
    }
  }

  /**
   * Загружает историю чата. Сообщения, пришедшие по realtime во время запроса, не теряются.
   */
  async function load(id: number): Promise<void> {
    orderId.value = id
    loading.value = true
    error.value = ''
    try {
      const items = await orderMessageService.list(id)
      const known = new Set(items.map((item) => item.id))
      const extras = messages.value.filter(
        (item) => item.order_id === id && !known.has(item.id),
      )
      messages.value = [...items, ...extras].sort((a, b) => a.id - b.id)
      loaded.value = true
      if (open.value) {
        unreadCount.value = 0
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось загрузить чат.'
    } finally {
      loading.value = false
    }
  }

  /**
   * Отправляет черновик и сразу добавляет ответ сервера в ленту.
   */
  async function send(id: number): Promise<boolean> {
    const body = draft.value.trim()
    if (!body || sending.value) {
      return false
    }

    sending.value = true
    error.value = ''
    try {
      const message = await orderMessageService.send(id, body)
      ingest(message, message.user_id)
      draft.value = ''
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Не удалось отправить сообщение.'
      return false
    } finally {
      sending.value = false
    }
  }

  function reset(): void {
    open.value = false
    orderId.value = null
    messages.value = []
    draft.value = ''
    loading.value = false
    sending.value = false
    error.value = ''
    unreadCount.value = 0
    loaded.value = false
  }

  return {
    open,
    orderId,
    messages,
    draft,
    loading,
    sending,
    error,
    unreadCount,
    loaded,
    toggle,
    close,
    bindOrder,
    ingest,
    load,
    send,
    reset,
  }
})
