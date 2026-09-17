<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAvatarUpload } from '@/composables'

/** id загруженного файла: сохраняется в users.avatar_id при отправке формы. */
const fileId = defineModel<number | null>({ default: null })
/** URL превью: текущий аватар или только что загруженный файл. */
const url = defineModel<string | null>('url', { default: null })

const props = defineProps<{
  /** Имя пользователя — для буквы-заглушки, пока аватара нет. */
  name?: string
}>()

const { uploading, error, upload } = useAvatarUpload()
const input = ref<HTMLInputElement | null>(null)

const initial = computed(() => (props.name?.trim().charAt(0) ?? '').toUpperCase() || '?')

function openPicker(): void {
  input.value?.click()
}

/**
 * Загружает выбранное изображение сразу, сохраняя наверху id и URL файла.
 */
async function onSelect(event: Event): Promise<void> {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  // Сбрасываем значение: иначе повторный выбор того же файла не вызовет change.
  target.value = ''

  if (!file) {
    return
  }

  const uploaded = await upload(file)
  if (!uploaded) {
    return
  }

  fileId.value = uploaded.id
  url.value = uploaded.url
}
</script>

<template>
  <div class="flex items-center gap-4">
    <img
      v-if="url"
      :src="url"
      alt="Аватар"
      class="h-20 w-20 shrink-0 rounded-full object-cover ring-4 ring-accent-nav/50"
    />
    <span
      v-else
      class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-surface-muted text-2xl font-semibold text-text-primary ring-4 ring-accent-nav/50 dark:bg-zinc-800 dark:text-zinc-100"
      aria-hidden="true"
    >
      {{ initial }}
    </span>

    <div class="min-w-0">
      <button
        type="button"
        class="rounded-full border border-border-subtle bg-white px-4 py-2 text-sm font-medium text-text-primary transition hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
        :disabled="uploading"
        @click="openPicker"
      >
        {{ uploading ? 'Загрузка…' : 'Изменить фото' }}
      </button>
      <p class="mt-1 text-xs text-text-secondary">JPG, PNG, WebP или GIF до 5 МБ</p>
      <p v-if="error" class="mt-1 text-xs text-accent-danger">{{ error }}</p>
    </div>

    <input
      ref="input"
      type="file"
      accept="image/jpeg,image/png,image/webp,image/gif"
      class="hidden"
      @change="onSelect"
    />
  </div>
</template>
