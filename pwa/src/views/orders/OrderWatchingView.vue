<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import OrderExecuteMap from '@/components/map/OrderExecuteMap.vue'
import OrderChatDriver from '@/components/orders/OrderChatDriver.vue'
import OrderWatchingPanel from '@/components/orders/OrderWatchingPanel.vue'
import { useOrderWatchingDriver } from '@/composables/useOrderWatchingDriver'

const route = useRoute()

const orderId = computed(() => {
  const raw = route.params.orderId
  const value = Number(Array.isArray(raw) ? raw[0] : raw)
  return Number.isInteger(value) && value > 0 ? value : null
})

const {
  executing,
  position,
  remainingRoute,
  currentPoint,
  destination,
  loading,
  error,
  routeError,
} = useOrderWatchingDriver(orderId)

const stepLabel = computed(() => {
  const points = executing.value?.points ?? []
  const index = currentPoint.value
    ? points.findIndex((point) => point.id === currentPoint.value?.id)
    : -1
  if (!points.length || index < 0) {
    return 'Наблюдение'
  }
  return `Точка ${index + 1} из ${points.length}`
})

const pointDescription = computed(
  () => currentPoint.value?.order_point?.description || 'Исполнитель в пути',
)

const pointAddress = computed(() => currentPoint.value?.order_point?.address ?? null)
</script>

<template>
  <div class="relative flex min-h-0 flex-1 flex-col">
    <p v-if="!orderId" class="px-4 pt-4 text-sm text-accent-danger">
      Некорректный заказ.
    </p>
    <p v-else-if="loading && !executing" class="px-4 pt-4 text-sm text-text-secondary">
      Загружаем маршрут…
    </p>
    <p v-else-if="error && !executing" class="px-4 pt-4 text-sm text-accent-danger">
      {{ error }}
    </p>

    <template v-else-if="executing">
      <div class="min-h-0 flex-1">
        <OrderExecuteMap :destination="destination" :position="position" :route="remainingRoute" />
      </div>
      <OrderWatchingPanel
        v-if="currentPoint || error"
        :step-label="stepLabel"
        :description="pointDescription"
        :address="pointAddress"
        :error="error || routeError"
      />
    </template>
    <OrderChatDriver :order-id="orderId" />
  </div>
</template>
