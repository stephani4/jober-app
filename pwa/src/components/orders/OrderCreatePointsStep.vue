<script setup lang="ts">
import { computed, ref } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import VkMapPicker from '@/components/map/VkMapPicker.vue'
import type { DraftOrderPoint } from '@/stores/orderCreate'

const props = defineProps<{
  points: DraftOrderPoint[]
}>()

const emit = defineEmits<{
  'update:points': [points: DraftOrderPoint[]]
  add: []
  remove: [clientId: string]
  locate: [payload: { clientId: string; lat: number; lon: number; address: string | null }]
}>()

const pickingId = ref<string | null>(null)

const list = computed({
  get: () => props.points,
  set: (value) => emit('update:points', value),
})

const pickingPoint = computed(() => props.points.find((point) => point.clientId === pickingId.value))

function onMapSelect(payload: { lat: number; lon: number; address: string | null }): void {
  if (!pickingId.value) {
    return
  }
  emit('locate', { clientId: pickingId.value, ...payload })
}

function pointLabel(point: DraftOrderPoint): string {
  if (point.address) {
    return point.address
  }
  if (point.lat != null && point.lon != null) {
    return `${point.lat.toFixed(5)}, ${point.lon.toFixed(5)}`
  }
  return 'Точка не выбрана'
}
</script>

<template>
  <div class="space-y-3">
    <VueDraggable v-model="list" handle=".drag-handle" :animation="180" class="space-y-3">
      <article
        v-for="(element, index) in list"
        :key="element.clientId"
        class="rounded-card border border-border-subtle bg-surface-card p-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
      >
        <div class="mb-3 flex items-center justify-between gap-2">
          <button
            type="button"
            class="drag-handle flex h-10 w-10 cursor-grab items-center justify-center rounded-full bg-surface-muted text-text-secondary active:cursor-grabbing dark:bg-zinc-800"
            aria-label="Перетащить точку"
          >
            {{ index + 1 }}
          </button>
          <button
            v-if="list.length > 1"
            type="button"
            class="text-sm text-accent-danger"
            @click="emit('remove', element.clientId)"
          >
            Удалить
          </button>
        </div>

        <label class="block text-sm text-text-secondary" :for="`point-desc-${element.clientId}`">
          Что сделать в точке
        </label>
        <textarea
          :id="`point-desc-${element.clientId}`"
          v-model="element.description"
          rows="3"
          class="mt-1 w-full rounded-xl border border-border-subtle bg-surface-muted px-3 py-2 text-text-primary outline-none dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-100"
          placeholder="Забрать документы у секретаря"
        />

        <p class="mt-3 text-sm text-text-secondary">{{ pointLabel(element) }}</p>
        <button
          type="button"
          class="mt-2 w-full rounded-xl border border-border-subtle px-4 py-3 text-sm text-text-primary dark:border-white/10 dark:text-zinc-100"
          @click="pickingId = element.clientId"
        >
          Выбрать на карте
        </button>
      </article>
    </VueDraggable>

    <button
      type="button"
      class="w-full rounded-card border border-dashed border-border-subtle px-4 py-3 text-sm text-text-secondary dark:border-white/20"
      @click="emit('add')"
    >
      Добавить точку
    </button>

    <VkMapPicker
      v-if="pickingId"
      :lat="pickingPoint?.lat"
      :lon="pickingPoint?.lon"
      @select="onMapSelect"
      @close="pickingId = null"
    />
  </div>
</template>
