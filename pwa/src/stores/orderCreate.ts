import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { CreateOrderPayload } from '@/schemas/order'

export interface DraftOrderPoint {
  clientId: string
  description: string
  address: string | null
  lat: number | null
  lon: number | null
}

function createPoint(): DraftOrderPoint {
  return {
    clientId: crypto.randomUUID(),
    description: '',
    address: null,
    lat: null,
    lon: null,
  }
}

export const useOrderCreateStore = defineStore('orderCreate', () => {
  const step = ref(1)
  const points = ref<DraftOrderPoint[]>([createPoint()])
  const cost = ref<number | null>(null)
  const description = ref('')
  const submitting = ref(false)
  const error = ref('')

  const canGoStep2 = computed(() =>
    points.value.length > 0 &&
    points.value.every((point) => point.description.trim().length > 0 && point.lat !== null && point.lon !== null),
  )

  const canGoStep3 = computed(() => typeof cost.value === 'number' && cost.value > 0)

  function addPoint(): void {
    points.value.push(createPoint())
  }

  function removePoint(clientId: string): void {
    if (points.value.length === 1) {
      return
    }
    points.value = points.value.filter((point) => point.clientId !== clientId)
  }

  function setPoints(next: DraftOrderPoint[]): void {
    points.value = next
  }

  function setPointLocation(clientId: string, location: { lat: number; lon: number; address: string | null }): void {
    const point = points.value.find((item) => item.clientId === clientId)
    if (!point) {
      return
    }
    point.lat = location.lat
    point.lon = location.lon
    point.address = location.address
  }

  function payload(): CreateOrderPayload {
    return {
      description: description.value.trim(),
      cost: Number(cost.value),
      points: points.value.map((point, index) => ({
        description: point.description.trim(),
        address: point.address,
        lat: Number(point.lat),
        lon: Number(point.lon),
        position: index + 1,
      })),
    }
  }

  function reset(): void {
    step.value = 1
    points.value = [createPoint()]
    cost.value = null
    description.value = ''
    submitting.value = false
    error.value = ''
  }

  return {
    step,
    points,
    cost,
    description,
    submitting,
    error,
    canGoStep2,
    canGoStep3,
    addPoint,
    removePoint,
    setPoints,
    setPointLocation,
    payload,
    reset,
  }
})
