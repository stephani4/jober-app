import { z } from 'zod'

/**
 * Загруженный на бэкенд файл (общая таблица files).
 * url — относительная ссылка на API, по ней файл отдаётся в <img src>.
 */
export const uploadedFileSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  extension: z.string(),
  size: z.number().int().nonnegative(),
  url: z.string().min(1),
})

export type UploadedFile = z.infer<typeof uploadedFileSchema>
