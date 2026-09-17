import { z } from 'zod'

/**
 * Краткая рабочая зона из profile:areas — без геометрии, только для выбора в форме.
 */
export const workingAreaOptionSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  type: z.enum(['city', 'other']),
})

export const workingAreaOptionListSchema = z.array(workingAreaOptionSchema)

export type WorkingAreaOption = z.infer<typeof workingAreaOptionSchema>