import { z } from 'zod'

export const realtimeTokenSchema = z.object({
  token: z.string().min(1),
})

export type RealtimeToken = z.infer<typeof realtimeTokenSchema>
export type RealtimeStatus = 'idle' | 'connecting' | 'connected' | 'disconnected'
