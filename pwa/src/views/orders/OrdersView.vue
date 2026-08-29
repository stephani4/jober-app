<script setup lang="ts">
import { useRouter } from 'vue-router'
import OrderCard from '@/components/orders/OrderCard.vue'
import { useAuth, useOrders } from '@/composables'
import type { Order } from '@/schemas/order'

const router = useRouter()
const { user } = useAuth()
const { items, loaded } = useOrders()

function canWatch(order: Order): boolean {
  return order.status === 'process' && order.user_id === user.value?.id
}

function onWatch(order: Order): void {
  void router.push({ name: 'order-watching', params: { orderId: String(order.id) } })
}
</script>

<template>
  <section class="space-y-4">
    <div class="grid gap-3 sm:grid-cols-3">
      <div
        class="rounded-card border border-border-subtle bg-surface-card p-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
      >
        <p class="text-sm text-text-secondary">Активные</p>
        <p class="mt-2 text-3xl text-text-primary dark:text-zinc-100">{{ items.length }}</p>
      </div>
      <div
        class="rounded-card border border-border-subtle bg-surface-card p-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
      >
        <p class="text-sm text-text-secondary">Мои отклики</p>
        <p class="mt-2 text-3xl text-text-primary dark:text-zinc-100">0</p>
      </div>
      <div
        class="rounded-card border border-border-subtle bg-surface-card p-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
      >
        <p class="text-sm text-text-secondary">Роль</p>
        <p class="mt-2 text-lg text-text-primary capitalize dark:text-zinc-100">
          {{ user?.role === 'executor' ? 'Исполнитель' : 'Заказчик' }}
        </p>
      </div>
    </div>

    <p v-if="loaded && items.length === 0" class="text-sm text-text-secondary">
      Нет активных заказов.
    </p>

    <div class="space-y-3">
      <OrderCard
        v-for="order in items"
        :key="order.id"
        :order="order"
        :watchable="canWatch(order)"
        @watch="onWatch(order)"
      />
    </div>
  </section>
</template>
