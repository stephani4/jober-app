<script setup lang="ts">
import { useTheme } from '@/composables'

const { isDark, toggle } = useTheme()
</script>

<template>
  <button
    type="button"
    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
    :aria-label="isDark ? 'Включить светлую тему' : 'Включить тёмную тему'"
    @click="toggle()"
  >
    {{ isDark ? 'Светлая' : 'Тёмная' }}
  </button>
</template>
