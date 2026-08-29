<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  count: number
}>()

const label = computed(() => (props.count > 99 ? '99+' : String(props.count)))
</script>

<template>
  <span
    v-if="count > 0"
    class="inline-flex min-w-4 items-center justify-center rounded-full bg-accent-danger px-1 text-[10px] leading-4 text-white"
    aria-label="Непрочитанные уведомления"
  >
    {{ label }}
  </span>
</template>
