<script setup lang="ts">
import { useOrderDecline } from '@/composables/useOrderDecline'

const { open, loading, cancel, confirm } = useOrderDecline()
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[90] flex items-end justify-center bg-black/40 px-4 pb-8 pt-16 sm:items-center"
      @click.self="cancel"
    >
      <article
        class="w-full max-w-md overflow-hidden rounded-3xl bg-surface-card p-5 shadow-[var(--shadow-card)] dark:bg-zinc-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="order-decline-title"
      >
        <h2 id="order-decline-title" class="text-lg text-text-primary dark:text-zinc-100">
          Отказаться от выполнения?
        </h2>
        <p class="mt-2 text-sm text-text-secondary">
          Заказ вернётся в поиск и станет доступен другим исполнителям. Ваше выполнение будет
          остановлено.
        </p>
        <div class="mt-5 flex gap-3">
          <button
            type="button"
            class="flex-1 rounded-xl border border-border-subtle px-4 py-3 text-text-primary disabled:opacity-50 dark:border-white/10 dark:text-zinc-100"
            :disabled="loading"
            @click="cancel"
          >
            Продолжить выполнение
          </button>
          <button
            type="button"
            class="flex-1 rounded-full bg-accent-danger px-4 py-3 text-white transition hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="loading"
            @click="confirm"
          >
            {{ loading ? 'Отменяем…' : 'Отказаться' }}
          </button>
        </div>
      </article>
    </div>
  </Teleport>
</template>
