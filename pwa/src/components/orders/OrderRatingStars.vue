<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useOrderRating } from '@/composables/useOrderRating'
import type { Order } from '@/schemas/order'

const props = defineProps<{
  orderId: number
  rating?: number | null
  canRate?: boolean
}>()

const emit = defineEmits<{
  rated: [order: Order]
}>()

const { rate, busy } = useOrderRating()
const error = ref('')
/** Черновик до нажатия «Подтвердить»; в БД ещё не пишется. */
const draft = ref<number | null>(null)

watch(
  () => props.rating,
  (value) => {
    if (value) {
      draft.value = null
    }
  },
)

const saved = computed(() => props.rating ?? null)
const value = computed(() => saved.value ?? draft.value ?? 0)
const interactive = computed(() => Boolean(props.canRate) && saved.value == null && !busy.value)
const showConfirm = computed(() => interactive.value && draft.value != null)

function onSelect(star: number): void {
  if (!interactive.value) {
    return
  }
  error.value = ''
  draft.value = star
}

async function onConfirm(): Promise<void> {
  if (!showConfirm.value || draft.value == null) {
    return
  }
  error.value = ''
  try {
    const order = await rate(props.orderId, draft.value)
    draft.value = null
    emit('rated', order)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Не удалось сохранить оценку.'
  }
}
</script>

<template>
  <div v-if="canRate || rating" class="mt-3">
    <p class="text-sm text-text-secondary">Качество выполнения</p>
    <div class="mt-1 flex items-center gap-1">
      <div class="flex min-w-0 flex-1 items-center" role="group" aria-label="Оценка от 1 до 5">
        <button
          v-for="star in 5"
          :key="star"
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-full text-amber-400 disabled:opacity-50"
          :disabled="!interactive"
          :aria-label="`${star} из 5`"
          :aria-pressed="star <= value"
          @click="onSelect(star)"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-7 w-7"
            :fill="star <= value ? 'currentColor' : 'none'"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="m12 3.6 2.47 5.01 5.53.8-4 3.9.94 5.5L12 16.2l-4.94 2.61.94-5.5-4-3.9 5.53-.8L12 3.6Z" />
          </svg>
        </button>
      </div>
      <button
        v-if="showConfirm"
        type="button"
        class="flex shrink-0 items-center gap-1.5 rounded-full bg-accent-nav px-3 py-2 text-sm text-white disabled:opacity-50"
        :disabled="busy"
        @click="onConfirm"
      >
        <svg
          viewBox="0 0 24 24"
          class="h-4 w-4"
          fill="none"
          stroke="currentColor"
          stroke-width="2.25"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M20 6 9 17l-5-5" />
        </svg>
        Подтвердить
      </button>
    </div>
    <p v-if="error" class="mt-1 text-xs text-accent-danger">{{ error }}</p>
  </div>
</template>
