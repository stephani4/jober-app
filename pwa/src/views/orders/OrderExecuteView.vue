<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import OrderExecuteMap from '@/components/map/OrderExecuteMap.vue'
import OrderChatDriver from '@/components/orders/OrderChatDriver.vue'
import OrderExecutePanel from '@/components/orders/OrderExecutePanel.vue'
import { useOrderExecuteDriver } from '@/composables/useOrderExecuteDriver'

const route = useRoute()

const orderId = computed(() => {
  const raw = route.params.orderId
  const value = Number(Array.isArray(raw) ? raw[0] : raw)
  return Number.isInteger(value) && value > 0 ? value : null
})

const {
  executing,
  loading,
  submitting,
  error,
  geoError,
  routeError,
  position,
  remainingRoute,
  currentPoint,
  stepIndex,
  isLast,
  destination,
  simulateGeo,
  applySimulated,
  completeCurrent,
} = useOrderExecuteDriver(orderId)

const stepLabel = computed(() => {
  const total = executing.value?.points.length ?? 0
  if (!total || stepIndex.value < 0) {
    return 'Выполнение'
  }
  return `Точка ${stepIndex.value + 1} из ${total}`
})

const pointDescription = computed(
  () => currentPoint.value?.order_point?.description || 'Нет описания точки',
)

const pointAddress = computed(() => currentPoint.value?.order_point?.address ?? null)
</script>

<template>
  <div class="relative flex min-h-0 flex-1 flex-col">
    <p v-if="!orderId" class="px-4 pt-4 text-sm text-accent-danger">
      Некорректный заказ.
    </p>
    <p v-else-if="loading && !executing" class="px-4 pt-4 text-sm text-text-secondary">
      Готовим маршрут…
    </p>
    <p v-else-if="error && !executing" class="px-4 pt-4 text-sm text-accent-danger">
      {{ error }}
    </p>

    <template v-else>
      <div class="min-h-0 flex-1">
        <OrderExecuteMap
          :destination="destination"
          :position="position"
          :route="remainingRoute"
          :draggable-position="simulateGeo"
          @simulate="applySimulated"
        />
      </div>
      <OrderExecutePanel
        v-if="currentPoint"
        :step-label="stepLabel"
        :description="pointDescription"
        :address="pointAddress"
        :is-last="isLast"
        :submitting="submitting"
        :error="error"
        :geo-error="geoError || routeError"
        @complete="completeCurrent"
      />
    </template>
    <OrderChatDriver :order-id="orderId" />
  </div>
</template>
