import { z } from 'zod'

export const adminSchema = z.object({
  id: z.number().int().positive(),
  name: z.string().min(1),
  email: z.string().email(),
  roles: z.array(z.string()).default([]),
  permissions: z.array(z.string()).default([]),
})

export type Admin = z.infer<typeof adminSchema>
