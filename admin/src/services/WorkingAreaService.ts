import { http } from '@/services/HttpClient'

export interface WorkingArea {
  id: number
  name: string
  type: 'city' | 'other'
  points: string // JSON array of [lon, lat]
  geometry: string // WKT
  created_at: string
  updated_at: string
}

export interface CreateWorkingAreaDto {
  name: string
  type: 'city' | 'other'
  points: string // JSON array of [lon, lat]
  geometry: string // WKT
}

export interface UpdateWorkingAreaDto {
  name?: string
  type?: 'city' | 'other'
  points?: string // JSON array of [lon, lat]
  geometry?: string // WKT
}

/**
 * Сервис для управления рабочими зонами.
 */
export class WorkingAreaService {
  async list(): Promise<WorkingArea[]> {
    const { data } = await http.client.get('/admin/working-areas')
    return data
  }

  async create(dto: CreateWorkingAreaDto): Promise<WorkingArea> {
    const { data } = await http.client.post('/admin/working-areas', dto)
    return data
  }

  async update(id: number, dto: UpdateWorkingAreaDto): Promise<WorkingArea> {
    const { data } = await http.client.put(`/admin/working-areas/${id}`, dto)
    return data
  }

  async delete(id: number): Promise<void> {
    await http.client.delete(`/admin/working-areas/${id}`)
  }

  async getById(id: number): Promise<WorkingArea> {
    const { data } = await http.client.get(`/admin/working-areas/${id}`)
    return data
  }
}

export const workingAreaService = new WorkingAreaService()
