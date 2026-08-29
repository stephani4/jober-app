<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import OrderChatDriver from '@/components/orders/OrderChatDriver.vue'

/** Просмотр чата завершённого заказа без возможности писать. */

const route = useRoute()

const orderId = computed(() => {
  const raw = route.params.orderId
  const value = Number(Array.isArray(raw) ? raw[0] : raw)
  return Number.isInteger(value) && value > 0 ? value : null
})
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col">
    <p v-if="!orderId" class="text-sm text-accent-danger">Некорректный заказ.</p>
    <OrderChatDriver v-else :order-id="orderId" readonly />
  </div>
</template>
