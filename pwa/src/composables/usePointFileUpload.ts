import { ref } from 'vue'
import { isAxiosError } from 'axios'
import { fileService } from '@/services/FileService'
import type { UploadedFile } from '@/schemas/file'

/** Ограничения совпадают с валидацией бэкенда (config/uploads.php attachment). */
export const POINT_FILE_ALLOWED_EXTENSIONS = ['docx', 'pdf', 'jpg', 'jpeg', 'png', 'webp']
export const POINT_FILE_MAX_SIZE_MB = 10

/**
 * Проверяет расширение вложения точки заказа.
 */
function isAllowedAttachment(file: File): boolean {
  const extension = file.name.split('.').pop()?.toLowerCase() ?? ''

  return POINT_FILE_ALLOWED_EXTENSIONS.includes(extension)
}

/**
 * Загрузка файла точки: сразу на сервер, в форму попадает id.
 */
export function usePointFileUpload() {
  const uploading = ref(false)
  const error = ref('')

  /**
   * Проверяет файл на клиенте и отправляет его на бэкенд.
   */
  async function upload(file: File): Promise<UploadedFile | null> {
    error.value = ''

    if (!isAllowedAttachment(file)) {
      error.value = 'Поддерживаются DOCX, PDF, JPG, PNG и WebP.'
      return null
    }

    if (file.size > POINT_FILE_MAX_SIZE_MB * 1024 * 1024) {
      error.value = `Размер файла не должен превышать ${POINT_FILE_MAX_SIZE_MB} МБ.`
      return null
    }

    uploading.value = true
    try {
      return await fileService.upload(file)
    } catch (err) {
      error.value = isAxiosError(err)
        ? err.response?.data?.errors?.file?.[0] || err.response?.data?.message || 'Не удалось загрузить файл.'
        : 'Не удалось загрузить файл.'
      return null
    } finally {
      uploading.value = false
    }
  }

  return { uploading, error, upload }
}
