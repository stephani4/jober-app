import { ref } from 'vue'
import { isAxiosError } from 'axios'
import { fileService } from '@/services/FileService'
import type { UploadedFile } from '@/schemas/file'

/** Ограничения совпадают с валидацией бэкенда (config/uploads.php). */
export const AVATAR_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
export const AVATAR_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif']
export const AVATAR_MAX_SIZE_MB = 5

/** Проверяет, что выбран именно файл изображения. */
function isAllowedImage(file: File): boolean {
  if (file.type) {
    return AVATAR_ALLOWED_TYPES.includes(file.type)
  }

  // Некоторые браузеры не определяют MIME — тогда проверяем по расширению.
  const extension = file.name.split('.').pop()?.toLowerCase() ?? ''

  return AVATAR_ALLOWED_EXTENSIONS.includes(extension)
}

/**
 * Загрузка аватара: файл уходит на бэкенд сразу после выбора,
 * в форму возвращается id файла, а users.avatar_id проставляется при сохранении профиля.
 */
export function useAvatarUpload() {
  const uploading = ref(false)
  const error = ref('')

  /**
   * Проверяет файл на клиенте и отправляет его на бэкенд.
   *
   * @param file выбранное изображение
   * @returns запись файла или null, если проверка/загрузка не прошла
   */
  async function upload(file: File): Promise<UploadedFile | null> {
    error.value = ''

    if (!isAllowedImage(file)) {
      error.value = 'Поддерживаются JPG, PNG, WebP и GIF.'
      return null
    }

    if (file.size > AVATAR_MAX_SIZE_MB * 1024 * 1024) {
      error.value = `Размер изображения не должен превышать ${AVATAR_MAX_SIZE_MB} МБ.`
      return null
    }

    uploading.value = true
    try {
      return await fileService.uploadAvatar(file)
    } catch (err) {
      error.value = isAxiosError(err)
        ? err.response?.data?.errors?.file?.[0] || err.response?.data?.message || 'Не удалось загрузить изображение.'
        : 'Не удалось загрузить изображение.'
      return null
    } finally {
      uploading.value = false
    }
  }

  return { uploading, error, upload }
}