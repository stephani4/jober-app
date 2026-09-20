import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminAdministratorService, type AdminListFilters } from '@/services/AdminAdministratorService'
import type { Admin } from '@/schemas/admin'

export const useAdminsStore = defineStore('admins', () => {
  const items = ref<Admin[]>([])
  const nextCursor = ref<number | null>(null)
  const loading = ref(false)
  const loadingMore = ref(false)
  const filters = ref<AdminListFilters>({})

  /** Загружает первую страницу с новыми (или текущими) фильтрами. */
  async function fetchFirst(next?: AdminListFilters): Promise<void> {
    if (next !== undefined) {
      filters.value = next
    }
    loading.value = true
    try {
      const page = await adminAdministratorService.list(filters.value)
      items.value = page.items
      nextCursor.value = page.next_cursor
    } finally {
      loading.value = false
    }
  }

  /** Догружает следующую страницу (по 15 записей). */
  async function loadMore(): Promise<void> {
    if (nextCursor.value == null || loadingMore.value || loading.value) {
      return
    }
    loadingMore.value = true
    try {
      const page = await adminAdministratorService.list(filters.value, nextCursor.value)
      const known = new Set(items.value.map((item) => item.id))
      items.value = [...items.value, ...page.items.filter((item) => !known.has(item.id))]
      nextCursor.value = page.next_cursor
    } finally {
      loadingMore.value = false
    }
  }

  return {
    items,
    nextCursor,
    loading,
    loadingMore,
    filters,
    fetchFirst,
    loadMore,
  }
})
