import { Centrifuge } from 'centrifuge'
import { http } from '@/services/HttpClient'
import { realtimeTokenSchema } from '@/schemas/realtime'

export type PublicationHandler = (channel: string, data: unknown) => void

/**
 * Низкоуровневый клиент Centrifugo: соединение, RPC и публикации.
 */
export class CentrifugoClient {
  private centrifuge: Centrifuge | null = null
  private publicationHandlers = new Set<PublicationHandler>()

  isConnected(): boolean {
    return this.centrifuge?.state === 'connected'
  }

  async connect(): Promise<void> {
    if (this.centrifuge) {
      return
    }

    const centrifuge = new Centrifuge(this.websocketUrl(), {
      getToken: () => this.fetchToken(),
    })

    centrifuge.on('publication', (ctx) => {
      this.publicationHandlers.forEach((handler) => handler(ctx.channel, ctx.data))
    })

    this.centrifuge = centrifuge

    try {
      await new Promise<void>((resolve, reject) => {
        const timer = window.setTimeout(() => {
          reject(new Error('Таймаут подключения к realtime'))
        }, 10000)

        const onConnected = () => {
          window.clearTimeout(timer)
          centrifuge.removeListener('disconnected', onDisconnected)
          resolve()
        }
        const onDisconnected = (ctx: { reason?: string }) => {
          window.clearTimeout(timer)
          centrifuge.removeListener('connected', onConnected)
          reject(new Error(ctx.reason || 'Realtime отключён'))
        }

        centrifuge.once('connected', onConnected)
        centrifuge.once('disconnected', onDisconnected)
        centrifuge.connect()
      })
    } catch (error) {
      centrifuge.disconnect()
      this.centrifuge = null
      throw error
    }
  }

  disconnect(): void {
    this.centrifuge?.disconnect()
    this.centrifuge = null
  }

  onPublication(handler: PublicationHandler): () => void {
    this.publicationHandlers.add(handler)
    return () => {
      this.publicationHandlers.delete(handler)
    }
  }

  async rpc<T>(method: string, data: unknown = {}): Promise<T> {
    if (!this.centrifuge) {
      throw new Error('Realtime не подключён')
    }

    try {
      const result = await this.centrifuge.rpc(method, data)
      return result.data as T
    } catch (err) {
      const message =
        err instanceof Error
          ? err.message
          : typeof err === 'object' && err && 'message' in err
            ? String((err as { message: unknown }).message)
            : 'Ошибка realtime-запроса'
      throw new Error(message)
    }
  }

  private websocketUrl(): string {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:'
    return `${protocol}//${window.location.host}/connection/websocket`
  }

  private async fetchToken(): Promise<string> {
    const { data } = await http.client.get('/realtime/token')
    return realtimeTokenSchema.parse(data).token
  }
}

export const centrifugoClient = new CentrifugoClient()
