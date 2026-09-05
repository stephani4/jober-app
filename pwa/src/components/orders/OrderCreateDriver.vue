<script setup lang="ts">
import { computed } from 'vue'
import OrderCreatePointsStep from '@/components/orders/OrderCreatePointsStep.vue'
import OrderCreateCostStep from '@/components/orders/OrderCreateCostStep.vue'
import OrderCreateSummaryStep from '@/components/orders/OrderCreateSummaryStep.vue'
import { useOrderCreateDriver } from '@/composables'

const emit = defineEmits<{
  created: []
}>()

const {
  step,
  orderType,
  points,
  cost,
  description,
  submitting,
  error,
  isSinglePoint,
  next,
  back,
  submit,
  addPoint,
  removePoint,
  setPoints,
  setPointLocation,
} = useOrderCreateDriver()

const steps = computed(() => [
  { value: 1, label: isSinglePoint.value ? 'Доставка' : 'Точки' },
  { value: 2, label: 'Стоимость' },
  { value: 3, label: 'Итог' },
])

async function onSubmit(): Promise<void> {
  const ok = await submit()
  if (ok) {
    emit('created')
  }
}
</script>

<template>
  <div class="space-y-4">
    <ol class="grid grid-cols-3 gap-2">
      <li
        v-for="item in steps"
        :key="item.value"
        class="rounded-xl px-2 py-2 text-center text-xs"
        :class="
          step === item.value
            ? 'bg-accent-nav text-white'
            : step > item.value
              ? 'bg-surface-card text-text-primary dark:bg-zinc-800 dark:text-zinc-100'
              : 'bg-surface-muted text-text-secondary dark:bg-zinc-900'
        "
      >
        {{ item.label }}
      </li>
    </ol>

    <p
      v-if="orderType"
      class="rounded-xl border border-border-subtle bg-surface-muted px-4 py-3 text-sm dark:border-white/10 dark:bg-zinc-900"
    >
      <span class="text-text-primary dark:text-zinc-100">{{ orderType.name }}</span>
      <span class="mt-0.5 block text-text-secondary">{{ orderType.description }}</span>
    </p>

    <OrderCreatePointsStep
      v-if="step === 1"
      :points="points"
      :single-point="isSinglePoint"
      @update:points="setPoints"
      @add="addPoint"
      @remove="removePoint"
      @locate="setPointLocation($event.clientId, $event)"
    />
    <OrderCreateCostStep
      v-else-if="step === 2"
      :cost="cost"
      :description="description"
      @update:cost="cost = $event"
      @update:description="description = $event"
    />
    <OrderCreateSummaryStep
      v-else
      :points="points"
      :cost="cost"
      :description="description"
      :order-type-name="orderType?.name ?? null"
      :single-point="isSinglePoint"
    />

    <p
      v-if="error"
      class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200"
    >
      {{ error }}
    </p>

    <div class="flex gap-3">
      <button
        v-if="step > 1"
        type="button"
        class="flex-1 rounded-xl border border-border-subtle px-4 py-3 text-text-primary dark:border-white/10 dark:text-zinc-100"
        @click="back()"
      >
        Назад
      </button>
      <button
        v-if="step < 3"
        type="button"
        class="flex-1 rounded-xl bg-accent-nav px-4 py-3 text-white disabled:opacity-50"
        @click="next()"
      >
        Далее
      </button>
      <button
        v-else
        type="button"
        class="flex-1 rounded-xl bg-accent-nav px-4 py-3 text-white disabled:opacity-50"
        :disabled="submitting"
        @click="onSubmit"
      >
        {{ submitting ? 'Создаём…' : 'Создать заказ' }}
      </button>
    </div>
  </div>
</template>
