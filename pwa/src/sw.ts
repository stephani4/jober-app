/// <reference lib="webworker" />
import { clientsClaim } from 'workbox-core'
import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching'

declare let self: ServiceWorkerGlobalScope

precacheAndRoute(self.__WB_MANIFEST)
cleanupOutdatedCaches()

self.skipWaiting()
clientsClaim()

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    void self.skipWaiting()
  }
})

type PushPayload = {
  title?: string
  body?: string
  url?: string
  tag?: string
  notification_id?: string
  order_id?: number | null
}

self.addEventListener('push', (event) => {
  const data = parsePushData(event)
  event.waitUntil(
    self.registration.showNotification(data.title || 'Jober', {
      body: data.body || '',
      icon: '/pwa-192x192.png',
      badge: '/pwa-192x192.png',
      tag: data.tag || 'jober',
      data: {
        url: data.url || '/notifications',
      },
    }),
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const target = resolveUrl(event.notification.data?.url)
  event.waitUntil(openOrFocus(target))
})

function parsePushData(event: PushEvent): PushPayload {
  if (!event.data) {
    return {}
  }
  try {
    return event.data.json() as PushPayload
  } catch {
    return { body: event.data.text() }
  }
}

function resolveUrl(url: unknown): string {
  if (typeof url !== 'string' || url.length === 0) {
    return self.location.origin + '/notifications'
  }
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  return new URL(url, self.location.origin).href
}

async function openOrFocus(url: string): Promise<void> {
  const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })
  const existing = windows.find((client) => 'focus' in client && client.url.startsWith(self.location.origin))
  if (existing && 'focus' in existing) {
    await existing.focus()
    if ('navigate' in existing) {
      await (existing as WindowClient).navigate(url)
    }
    return
  }
  await self.clients.openWindow(url)
}
