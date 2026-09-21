import { z } from 'zod'

/**
 * Файл из таблицы files. url — относительный путь API для просмотра или скачивания.
 */
export const uploadedFileSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  extension: z.string(),
  size: z.number().int().nonnegative(),
  url: z.string().min(1),
})

export type UploadedFile = z.infer<typeof uploadedFileSchema>
