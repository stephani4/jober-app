<script setup lang="ts">
import { computed, ref } from 'vue'

defineProps<{
  submitting: boolean
  error: string
}>()

const emit = defineEmits<{
  confirm: [code: string]
}>()

/** Этап подтверждения: исполнитель вводит 4-значный код, сообщённый заказчиком. */

const code = ref('')

const canConfirm = computed(() => /^\d{4}$/.test(code.value))

function onSubmit(): void {
  if (!canConfirm.value) {
    return
  }
  emit('confirm', code.value)
}
</script>

<template>
  <section
    class="rounded-t-3xl border border-border-subtle bg-surface-card px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
  >
    <p class="text-sm text-text-secondary">Подтверждение выполнения</p>
    <p class="mt-1 text-base text-text-primary dark:text-zinc-100">
      Заказ выполнен. Введите 4-значный код, который вам сообщит заказчик.
    </p>

    <p v-if="error" class="mt-3 text-sm text-accent-danger">
      {{ error }}
    </p>

    <form class="mt-4 flex items-end gap-2" @submit.prevent="onSubmit">
      <label class="sr-only" for="order-confirm-code">Код подтверждения</label>
      <input
        id="order-confirm-code"
        v-model="code"
        type="text"
        inputmode="numeric"
        autocomplete="one-time-code"
        maxlength="4"
        placeholder="0000"
        :disabled="submitting"
        class="min-h-11 flex-1 rounded-2xl border border-border-subtle bg-surface-muted px-4 py-2.5 text-center text-lg font-semibold tracking-[0.5em] text-text-primary outline-none dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-100"
      />
      <button
        type="submit"
        class="flex h-11 shrink-0 items-center justify-center rounded-full bg-accent-nav px-5 text-sm text-white transition hover:bg-accent-nav-hover disabled:opacity-50"
        :disabled="!canConfirm || submitting"
      >
        {{ submitting ? 'Подтверждаем…' : 'Подтвердить завершение' }}
      </button>
    </form>
  </section>
</template>
