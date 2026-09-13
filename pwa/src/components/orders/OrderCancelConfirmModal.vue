<script setup lang="ts">
import type { Order } from '@/schemas/order'

defineProps<{
  order: Order | null
  loading?: boolean
}>()

const emit = defineEmits<{
  confirm: []
  close: []
}>()
</script>

<template>
  <Teleport to="body">
    <div
      v-if="order"
      class="fixed inset-0 z-[90] flex items-end justify-center bg-black/40 px-4 pb-8 pt-16 sm:items-center"
      @click.self="emit('close')"
    >
      <article
        class="w-full max-w-md overflow-hidden rounded-3xl bg-surface-card p-5 shadow-[var(--shadow-card)] dark:bg-zinc-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="order-cancel-title"
      >
        <h2 id="order-cancel-title" class="text-lg text-text-primary dark:text-zinc-100">
          Отменить заказ?
        </h2>
        <p class="mt-2 text-sm text-text-secondary">
          Заказ «{{ order.description || 'без примечания' }}» будет отменён и перемещён в историю.
          <template v-if="order.status === 'process'">
            Исполнитель получит уведомление, выполнение остановится.
          </template>
          Вернуть заказ будет нельзя.
        </p>
        <div class="mt-5 flex gap-3">
          <button
            type="button"
            class="flex-1 rounded-xl border border-border-subtle px-4 py-3 text-text-primary disabled:opacity-50 dark:border-white/10 dark:text-zinc-100"
            :disabled="loading"
            @click="emit('close')"
          >
            Не отменять
          </button>
          <button
            type="button"
            class="flex-1 rounded-full bg-accent-danger px-4 py-3 text-white transition hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="loading"
            @click="emit('confirm')"
          >
            {{ loading ? 'Отменяем…' : 'Отменить заказ' }}
          </button>
        </div>
      </article>
    </div>
  </Teleport>
</template>
