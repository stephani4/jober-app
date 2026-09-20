<script setup lang="ts">
import type { UploadedFile } from '@/schemas/file'

defineProps<{
  files: UploadedFile[]
}>()

/**
 * Ссылка на скачивание: бэкенд отдаёт файл как attachment.
 */
function downloadUrl(file: UploadedFile): string {
  const separator = file.url.includes('?') ? '&' : '?'
  return `${file.url}${separator}download=1`
}
</script>

<template>
  <ul v-if="files.length" class="mt-3 space-y-2">
    <li
      v-for="file in files"
      :key="file.id"
      class="flex items-center justify-between gap-2 rounded-xl bg-surface-muted px-3 py-2 dark:bg-zinc-800"
    >
      <span class="min-w-0 truncate text-sm text-text-primary dark:text-zinc-100">{{ file.name }}</span>
      <a
        :href="downloadUrl(file)"
        :download="file.name"
        class="shrink-0 text-sm text-accent-nav"
      >
        Скачать
      </a>
    </li>
  </ul>
</template>
