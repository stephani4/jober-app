<script setup lang="ts">
import { useOrderTypes } from '@/composables'
import { HELP_CARRY_TYPE_ID } from '@/schemas/order'

const { types, loading, error, pickerOpen, closePicker, selectType } = useOrderTypes()
</script>

<template>
  <Teleport to="body">
    <div
      v-if="pickerOpen"
      class="fixed inset-0 z-[90] flex items-end justify-center bg-black/40 px-4 pb-8 pt-16 sm:items-center"
      @click.self="closePicker()"
    >
      <article
        class="w-full max-w-lg overflow-hidden rounded-3xl bg-surface-card shadow-[var(--shadow-card)] dark:bg-zinc-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="order-type-title"
      >
        <div class="flex items-start justify-between gap-3 px-5 pt-5">
          <div>
            <h2 id="order-type-title" class="text-lg text-text-primary dark:text-zinc-100">
              Какой заказ?
            </h2>
            <p class="mt-1 text-sm text-text-secondary">Выберите вид — откроется форма</p>
          </div>
          <button
            type="button"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-text-secondary"
            aria-label="Закрыть"
            @click="closePicker()"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              stroke-width="1.75"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M18 6 6 18M6 6l12 12" />
            </svg>
          </button>
        </div>

        <p v-if="loading && types.length === 0" class="px-5 py-6 text-sm text-text-secondary">
          Загружаем виды заказов…
        </p>
        <p
          v-else-if="error && types.length === 0"
          class="px-5 py-6 text-sm text-accent-danger"
        >
          {{ error }}
        </p>
        <ul v-else class="space-y-2 px-4 py-4">
          <li v-for="type in types" :key="type.id">
            <button
              type="button"
              class="flex w-full items-center gap-3 rounded-2xl border border-border-subtle bg-surface-card px-4 py-3.5 text-left transition hover:bg-surface-muted dark:border-white/10 dark:bg-zinc-900 dark:hover:bg-zinc-800"
              @click="selectType(type)"
            >
              <span
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-surface-muted text-text-primary dark:bg-zinc-800 dark:text-zinc-100"
                aria-hidden="true"
              >
                <svg
                  v-if="type.max_points === 1"
                  viewBox="0 0 24 24"
                  class="h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.75"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M6 7h15l-1.5 9h-12z" />
                  <path d="M6 7 5 4H2" />
                  <circle cx="9" cy="20" r="1" />
                  <circle cx="18" cy="20" r="1" />
                </svg>
                <svg
                  v-else-if="type.id === HELP_CARRY_TYPE_ID"
                  viewBox="0 0 24 24"
                  class="h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.75"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <rect x="3" y="7" width="13" height="10" rx="1.5" />
                  <path d="M16 10h3l2 3v4h-5" />
                  <path d="M8 12h5" />
                </svg>
                <svg
                  v-else
                  viewBox="0 0 24 24"
                  class="h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.75"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M7 3h8l4 4v14H7z" />
                  <path d="M15 3v4h4" />
                  <path d="M10 13h6M10 17h4" />
                </svg>
              </span>
              <span class="min-w-0 flex-1">
                <span class="block text-base text-text-primary dark:text-zinc-100">{{ type.name }}</span>
                <span class="mt-0.5 block text-sm text-text-secondary">{{ type.description }}</span>
              </span>
              <svg
                viewBox="0 0 24 24"
                class="h-5 w-5 shrink-0 text-text-secondary"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <path d="m9 6 6 6-6 6" />
              </svg>
            </button>
          </li>
        </ul>
      </article>
    </div>
  </Teleport>
</template>
