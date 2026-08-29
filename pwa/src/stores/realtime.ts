import { defineStore } from 'pinia'
import { ref } from 'vue'
import { centrifugoClient } from '@/services/CentrifugoClient'
import type { RealtimeStatus } from '@/schemas/realtime'

export const useRealtimeStore = defineStore('realtime', () => {
  const status = ref<RealtimeStatus>('idle')

  async function connect(): Promise<void> {
    if (status.value === 'connected' || status.value === 'connecting') {
      return
    }

    status.value = 'connecting'
    try {
      await centrifugoClient.connect()
      status.value = 'connected'
    } catch {
      status.value = 'disconnected'
      throw new Error('Не удалось подключиться к realtime')
    }
  }

  function disconnect(): void {
    centrifugoClient.disconnect()
    status.value = 'disconnected'
  }

  return {
    status,
    connect,
    disconnect,
  }
})
