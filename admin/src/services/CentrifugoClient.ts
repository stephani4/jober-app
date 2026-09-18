import { Centrifuge } from 'centrifuge'

/** Обработчик публикации в любом канале. */
export type PublicationHandler = (channel: string, data: unknown) => void

/**
 * Низкоуровневый клиент Centrifugo для админки: соединение и публикации.
 */
export class CentrifugoClient {
  private centrifuge: Centrifuge | null = null
  private publicationHandlers = new Set<PublicationHandler>()

  isConnected(): boolean {
    return this.centrifuge?.state === 'connected'
  }

  /**
   * Подключается к realtime; tokenProvider отдаёт connection JWT.
   */
  async connect(tokenProvider: () => Promise<string>): Promise<void> {
    if (this.centrifuge) {
      return
    }

    const centrifuge = new Centrifuge(this.websocketUrl(), {
      getToken: tokenProvider,
    })

    centrifuge.on('publication', (ctx) => {
      this.publicationHandlers.forEach((handler) => handler(ctx.channel, ctx.data))
    })

    this.centrifuge = centrifuge

    try {
      await new Promise<void>((resolve, reject) => {
        const timer = window.setTimeout(() => {
          reject(new Error('Таймаут подключения к realtime'))
        }, 10_000)

        const onConnected = (): void => {
          window.clearTimeout(timer)
          centrifuge.removeListener('disconnected', onDisconnected)
          resolve()
        }
        const onDisconnected = (ctx: { reason?: string }): void => {
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

  /**
   * Подписка на публикации; возвращает функцию отписки.
   */
  onPublication(handler: PublicationHandler): () => void {
    this.publicationHandlers.add(handler)
    return () => {
      this.publicationHandlers.delete(handler)
    }
  }

  private websocketUrl(): string {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:'
    return `${protocol}//${window.location.host}/connection/websocket`
  }
}

export const centrifugoClient = new CentrifugoClient()
