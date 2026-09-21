<script setup lang="ts">
import { ref } from 'vue'
import { usePointFileUpload } from '@/composables/usePointFileUpload'
import type { UploadedFile } from '@/schemas/file'

const files = defineModel<UploadedFile[]>({ default: () => [] })

const { uploading, error, upload } = usePointFileUpload()
const input = ref<HTMLInputElement | null>(null)

function openPicker(): void {
  input.value?.click()
}

/**
 * Загружает выбранный файл сразу и добавляет его id в черновик точки.
 */
async function onSelect(event: Event): Promise<void> {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  target.value = ''

  if (!file) {
    return
  }

  const uploaded = await upload(file)
  if (!uploaded) {
    return
  }

  if (files.value.some((item) => item.id === uploaded.id)) {
    return
  }

  files.value = [...files.value, uploaded]
}

function remove(id: number): void {
  files.value = files.value.filter((item) => item.id !== id)
}
</script>

<template>
  <div class="mt-3">
    <p class="text-sm text-text-secondary">Файл к точке</p>
    <ul v-if="files.length" class="mt-2 space-y-1.5">
      <li
        v-for="item in files"
        :key="item.id"
        class="flex items-center justify-between gap-2 rounded-xl bg-surface-muted px-3 py-2 text-sm dark:bg-zinc-800"
      >
        <span class="min-w-0 truncate text-text-primary dark:text-zinc-100">{{ item.name }}</span>
        <button
          type="button"
          class="shrink-0 text-xs text-accent-danger"
          @click="remove(item.id)"
        >
          Убрать
        </button>
      </li>
    </ul>
    <button
      type="button"
      class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-border-subtle px-4 py-3 text-sm text-text-secondary disabled:opacity-50 dark:border-white/20"
      :disabled="uploading || files.length >= 10"
      @click="openPicker"
    >
      <svg
        viewBox="0 0 24 24"
        class="h-5 w-5 shrink-0"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="M21.44 11.05 12.25 20.24a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.82-2.83l8.49-8.48" />
      </svg>
      {{ uploading ? 'Загрузка…' : 'Прикрепить файл' }}
    </button>
    <p class="mt-1 text-xs text-text-secondary">DOCX, PDF, JPG, PNG или WebP до 10 МБ</p>
    <p v-if="error" class="mt-1 text-xs text-accent-danger">{{ error }}</p>
    <input
      ref="input"
      type="file"
      accept=".docx,.pdf,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
      class="hidden"
      @change="onSelect"
    />
  </div>
</template>
