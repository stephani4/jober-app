<script setup lang="ts">
import { useRouter } from 'vue-router'
import OrderCard from '@/components/orders/OrderCard.vue'
import { useAuth, useSearchOrders } from '@/composables'
import type { Order } from '@/schemas/order'

const router = useRouter()
const { user } = useAuth()
const { items, loaded } = useSearchOrders()

function canStart(order: Order): boolean {
  return user.value?.role === 'executor' && order.user_id !== user.value.id && order.status === 'wait'
}

function onStart(order: Order): void {
  void router.push({ name: 'order-execute', params: { orderId: String(order.id) } })
}
</script>

<template>
  <section class="space-y-3">
    <p v-if="loaded && items.length === 0" class="text-sm text-text-secondary">
      Сейчас нет доступных заказов. Новые появятся здесь в realtime.
    </p>
    <OrderCard
      v-for="order in items"
      :key="order.id"
      :order="order"
      :startable="canStart(order)"
      @start="onStart(order)"
    />
  </section>
</template>
