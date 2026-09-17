import { uploadedFileSchema, type UploadedFile } from '@/schemas/file'
import { http } from '@/services/HttpClient'

/**
 * Загрузка файлов на бэкенд (общая таблица files).
 */
export class FileService {
  /**
   * Загружает изображение аватара и возвращает запись файла с её id.
   * Content-Type задаём явно: клиент по умолчанию отправляет JSON,
   * а для FormData axios подставляет multipart с boundary браузера.
   */
  async uploadAvatar(file: File): Promise<UploadedFile> {
    const formData = new FormData()
    formData.append('file', file)

    const { data } = await http.client.post('/uploads/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    return uploadedFileSchema.parse(data)
  }
}

export const fileService = new FileService()