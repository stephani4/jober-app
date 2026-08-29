<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import type { AppNotification } from '@/schemas/notification'

const props = defineProps<{
  notification: AppNotification
}>()

const emit = defineEmits<{
  visible: [id: string]
}>()

const root = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

const timeLabel = computed(() => {
  if (!props.notification.created_at) {
    return ''
  }
  const date = new Date(props.notification.created_at)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
})

onMounted(() => {
  if (!root.value) {
    return
  }
  observer = new IntersectionObserver(
    (entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        emit('visible', props.notification.id)
      }
    },
    { threshold: 0.6 },
  )
  observer.observe(root.value)
})

onBeforeUnmount(() => {
  observer?.disconnect()
  observer = null
})
</script>

<template>
  <article
    ref="root"
    class="rounded-card border px-4 py-4 shadow-[var(--shadow-card)]"
    :class="
      notification.read_at
        ? 'border-border-subtle bg-surface-card dark:border-white/10 dark:bg-zinc-900'
        : 'border-zinc-300 bg-surface-card dark:border-white/20 dark:bg-zinc-900'
    "
  >
    <div class="flex items-start justify-between gap-3">
      <p class="text-base text-text-primary dark:text-zinc-100">
        {{ notification.title }}
      </p>
      <span
        v-if="!notification.read_at"
        class="mt-1 h-2 w-2 shrink-0 rounded-full bg-accent-danger"
        aria-hidden="true"
      />
    </div>
    <p class="mt-1 text-sm text-text-secondary">
      {{ notification.body }}
    </p>
    <p v-if="timeLabel" class="mt-2 text-xs text-text-secondary">
      {{ timeLabel }}
    </p>
  </article>
</template>
