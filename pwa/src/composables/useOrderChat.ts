import { computed, onBeforeUnmount, watch, type Ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { realtimeService } from '@/services/RealtimeService'
import { useOrderChatStore } from '@/stores/orderChat'

/**
 * Кнопка чата в шапке: видимость, непрочитанные и открытие панели.
 */
export function useOrderChat() {
  const store = useOrderChatStore()
  const route = useRoute()
  const { open, unreadCount } = storeToRefs(store)
  const showChat = computed(() => Boolean(route.meta.showChat))

  return {
    open,
    unreadCount,
    showChat,
    toggle: () => store.toggle(),
    close: () => store.close(),
  }
}

/**
 * Driver чата заказа: загрузка истории, realtime и отправка.
 * @param readonly если true — сразу грузит ленту, без отправки.
 */
export function useOrderChatDriver(orderId: Ref<number | null>, readonly = false) {
  const store = useOrderChatStore()
  const { user } = useAuth()
  const {
    open,
    messages,
    draft,
    loading,
    sending,
    error,
    unreadCount,
  } = storeToRefs(store)

  watch(
    orderId,
    (id) => {
      store.bindOrder(id)
      if (id && (open.value || readonly)) {
        void store.load(id)
      }
    },
    { immediate: true },
  )

  watch(open, (isOpen) => {
    const id = orderId.value
    if (!isOpen || !id) {
      return
    }
    void store.load(id)
  })

  const stopMessages = realtimeService.onOrderMessage((event) => {
    if (event.message.order_id !== orderId.value) {
      return
    }
    store.ingest(event.message, user.value?.id ?? null)
  })

  onBeforeUnmount(() => {
    stopMessages()
    store.reset()
  })

  async function send(): Promise<void> {
    const id = orderId.value
    if (!id) {
      return
    }
    await store.send(id)
  }

  return {
    open,
    messages,
    draft,
    loading,
    sending,
    error,
    unreadCount,
    send,
    close: () => store.close(),
  }
}
