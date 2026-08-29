<script setup lang="ts">
defineProps<{
  cost: number | null
  description: string
}>()

const emit = defineEmits<{
  'update:cost': [value: number | null]
  'update:description': [value: string]
}>()

function onCostInput(event: Event): void {
  const raw = (event.target as HTMLInputElement).value
  if (raw === '') {
    emit('update:cost', null)
    return
  }
  const next = Number(raw)
  emit('update:cost', Number.isFinite(next) ? next : null)
}
</script>

<template>
  <div class="space-y-4">
    <div>
      <label class="block text-sm text-text-secondary" for="order-cost">Стоимость заказа, ₽</label>
      <input
        id="order-cost"
        :value="cost ?? ''"
        type="number"
        min="1"
        step="50"
        inputmode="decimal"
        class="mt-1 w-full rounded-xl border border-border-subtle bg-surface-card px-4 py-3 text-text-primary outline-none dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-100"
        placeholder="1500"
        @input="onCostInput"
      />
    </div>

    <div>
      <label class="block text-sm text-text-secondary" for="order-note">Примечание к заказу</label>
      <textarea
        id="order-note"
        :value="description"
        rows="5"
        class="mt-1 w-full rounded-xl border border-border-subtle bg-surface-card px-4 py-3 text-text-primary outline-none dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-100"
        placeholder="Комментарий для исполнителя"
        @input="emit('update:description', ($event.target as HTMLTextAreaElement).value)"
      />
    </div>
  </div>
</template>
