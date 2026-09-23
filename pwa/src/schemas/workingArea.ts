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

/**
 * Полная рабочая зона из working-areas:list — с контурами для проверки точек заказа.
 */
export const workingAreaSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  type: z.enum(['city', 'other']),
  /** JSON-массив вершин контура [lng, lat], например `[[37.6,55.7], ...]`. */
  points: z.string().nullable().optional(),
  /** Геометрия зоны в WKT, например `POLYGON((lng lat, ...))`. */
  geometry: z.string().nullable().optional(),
})

export const workingAreaListSchema = z.array(workingAreaSchema)

export type WorkingArea = z.infer<typeof workingAreaSchema>