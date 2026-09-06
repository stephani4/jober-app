import { http } from '@/services/HttpClient'

export interface WorkingArea {
  id: number
  name: string
  type: 'city' | 'other'
  geometry: string
}

export class WorkingAreaService {
  async list(): Promise<WorkingArea[]> {
    const { data } = await http.client.get('/working-areas')
    return data
  }
}

export const workingAreaService = new WorkingAreaService()
