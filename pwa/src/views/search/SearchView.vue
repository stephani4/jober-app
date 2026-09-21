<script setup lang="ts">
import { useRouter } from 'vue-router'
import OrderCard from '@/components/orders/OrderCard.vue'
import { useAuth, useSearchOrders } from '@/composables'
import type { Order } from '@/schemas/order'

const router = useRouter()
const { user } = useAuth()
const { items } = useSearchOrders()

function canStart(order: Order): boolean {
  return user.value?.role === 'executor' && order.user_id !== user.value.id && order.status === 'wait'
}

function onStart(order: Order): void {
  void router.push({ name: 'order-execute', params: { orderId: String(order.id) } })
}
</script>

<template>
  <section class="flex min-h-0 flex-1 flex-col">
    <div
      v-if="items.length === 0"
      class="flex min-h-0 flex-1 flex-col items-center justify-center px-6 text-center"
    >
      <span
        class="h-10 w-10 animate-spin rounded-full border-2 border-border-subtle border-t-accent-nav"
        aria-hidden="true"
      />
      <p class="mt-4 text-sm text-text-secondary">
        Ищем заказ. Оповестим, сразу как появится
      </p>
    </div>
    <div v-else class="space-y-3">
      <OrderCard
        v-for="order in items"
        :key="order.id"
        :order="order"
        :startable="canStart(order)"
        @start="onStart(order)"
      />
    </div>
  </section>
</template>
