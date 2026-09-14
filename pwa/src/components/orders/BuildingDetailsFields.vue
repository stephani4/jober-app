<script setup lang="ts">
import type { DraftOrderPoint } from '@/stores/orderCreate'

/**
 * Поля доступа в выбранное на карте здание: подъезд, этаж, квартира и домофон.
 * Редактирует объект точки напрямую (как и остальные поля черновика).
 */
const props = defineProps<{
  point: DraftOrderPoint
}>()

/** Пустая строка → null, иначе целое число (без дробной части). */
function onNumericInput(field: 'entrance' | 'floor' | 'intercom', event: Event): void {
  const raw = (event.target as HTMLInputElement).value.trim()
  if (raw === '') {
    props.point[field] = null
    return
  }
  const value = Number(raw)
  props.point[field] = Number.isFinite(value) ? Math.trunc(value) : null
}

function onTextInput(event: Event): void {
  const value = (event.target as HTMLInputElement).value.trim()
  props.point.apartment = value || null
}

const numericField = (value: number | null): string => value ?? ''
</script>

<template>
  <fieldset class="mt-3 rounded-xl border border-border-subtle bg-surface-muted p-3 dark:border-white/10 dark:bg-zinc-800/60">
    <legend class="px-1 text-xs text-text-secondary">Данные здания (необязательно)</legend>
    <div class="grid grid-cols-2 gap-2">
      <label class="block">
        <span class="text-xs text-text-secondary">Подъезд</span>
        <input
          type="text"
          inputmode="numeric"
          :value="numericField(point.entrance)"
          maxlength="4"
          placeholder="—"
          class="mt-1 w-full rounded-lg border border-border-subtle bg-surface-card px-3 py-2 text-sm text-text-primary outline-none dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-100"
          @input="onNumericInput('entrance', $event)"
        />
      </label>
      <label class="block">
        <span class="text-xs text-text-secondary">Этаж</span>
        <input
          type="text"
          inputmode="numeric"
          :value="numericField(point.floor)"
          maxlength="4"
          placeholder="—"
          class="mt-1 w-full rounded-lg border border-border-subtle bg-surface-card px-3 py-2 text-sm text-text-primary outline-none dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-100"
          @input="onNumericInput('floor', $event)"
        />
      </label>
      <label class="block">
        <span class="text-xs text-text-secondary">Квартира/офис</span>
        <input
          type="text"
          :value="point.apartment ?? ''"
          maxlength="20"
          placeholder="—"
          class="mt-1 w-full rounded-lg border border-border-subtle bg-surface-card px-3 py-2 text-sm text-text-primary outline-none dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-100"
          @input="onTextInput"
        />
      </label>
      <label class="block">
        <span class="text-xs text-text-secondary">Домофон</span>
        <input
          type="text"
          inputmode="numeric"
          :value="numericField(point.intercom)"
          maxlength="6"
          placeholder="—"
          class="mt-1 w-full rounded-lg border border-border-subtle bg-surface-card px-3 py-2 text-sm text-text-primary outline-none dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-100"
          @input="onNumericInput('intercom', $event)"
        />
      </label>
    </div>
  </fieldset>
</template>