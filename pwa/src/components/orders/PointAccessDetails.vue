<script setup lang="ts">
import { computed } from 'vue'

type AccessPoint = {
  entrance?: number | null
  floor?: number | null
  apartment?: string | null
  intercom?: number | null
}

const props = defineProps<{
  point: AccessPoint | null | undefined
}>()

/** Чипы доступа в здание: подъезд, этаж, квартира, домофон. */
const chips = computed<string[]>(() => {
  const point = props.point
  if (!point) {
    return []
  }
  const parts: string[] = []
  if (point.entrance != null) {
    parts.push(`Подъезд ${point.entrance}`)
  }
  if (point.floor != null) {
    parts.push(`Этаж ${point.floor}`)
  }
  if (point.apartment) {
    parts.push(`Кв. ${point.apartment}`)
  }
  if (point.intercom != null) {
    parts.push(`Домофон ${point.intercom}`)
  }
  return parts
})
</script>

<template>
  <div v-if="chips.length" class="mt-2 flex flex-wrap gap-1.5">
    <span
      v-for="chip in chips"
      :key="chip"
      class="rounded-full bg-surface-muted px-2 py-0.5 text-[11px] font-medium text-text-secondary dark:bg-zinc-800 dark:text-zinc-400"
    >
      {{ chip }}
    </span>
  </div>
</template>