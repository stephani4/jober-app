<script setup lang="ts">
import type { UploadedFile } from '@/schemas/file'

defineProps<{
  files: UploadedFile[]
}>()

/**
 * Ссылка на скачивание: бэкенд отдаёт файл как attachment с исходным именем.
 */
function downloadUrl(file: UploadedFile): string {
  const separator = file.url.includes('?') ? '&' : '?'
  return `${file.url}${separator}download=1`
}
</script>

<template>
  <ul v-if="files.length" class="space-y-1.5">
    <li
      v-for="file in files"
      :key="file.id"
      class="flex items-start justify-between gap-2"
    >
      <span class="min-w-0 break-all text-xs text-text-primary dark:text-zinc-100">{{ file.name }}</span>
      <a
        :href="downloadUrl(file)"
        :download="file.name"
        class="shrink-0 text-xs font-medium text-sky-700 hover:underline dark:text-sky-300"
      >
        Скачать
      </a>
    </li>
  </ul>
  <span v-else class="text-xs text-text-secondary">—</span>
</template>
