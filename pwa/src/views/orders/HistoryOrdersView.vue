<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import OrderCard from '@/components/orders/OrderCard.vue'
import { useOrderHistory } from '@/composables/useOrderHistory'
import type { Order } from '@/schemas/order'

const router = useRouter()
const { items, loading, loadingMore, loaded, hasMore, loadMore } = useOrderHistory()

const sentinel = ref<HTMLElement | null>(null)
let moreObserver: IntersectionObserver | null = null

function onChat(order: Order): void {
  void router.push({ name: 'order-chat', params: { orderId: String(order.id) } })
}

onMounted(() => {
  moreObserver = new IntersectionObserver(
    (entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        void loadMore()
      }
    },
    { rootMargin: '120px' },
  )
  if (sentinel.value) {
    moreObserver.observe(sentinel.value)
  }
})

watch(sentinel, (el, prev) => {
  if (!moreObserver) {
    return
  }
  if (prev) {
    moreObserver.unobserve(prev)
  }
  if (el) {
    moreObserver.observe(el)
  }
})

onBeforeUnmount(() => {
  moreObserver?.disconnect()
  moreObserver = null
})
</script>

<template>
  <section class="space-y-4">
    <p v-if="loading && !loaded" class="text-sm text-text-secondary">Загружаем историю…</p>
    <p v-else-if="loaded && items.length === 0" class="text-sm text-text-secondary">
      Пока нет завершённых или отклонённых заказов.
    </p>

    <div class="space-y-3">
      <OrderCard
        v-for="order in items"
        :key="order.id"
        :order="order"
        :chatable="order.status === 'complete'"
        @chat="onChat(order)"
      />
    </div>

    <p v-if="loadingMore" class="text-center text-sm text-text-secondary">Ещё заказы…</p>
    <div v-if="hasMore" ref="sentinel" class="h-4" aria-hidden="true" />
  </section>
</template>
