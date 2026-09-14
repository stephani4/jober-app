import { storeToRefs } from 'pinia'
import { useUsersStore } from '@/stores/users'
import { adminUserService, type UserListFilters } from '@/services/AdminUserService'

/**
 * Список пользователей с фильтрами и пагинацией.
 */
export function useAdminUsers() {
  const store = useUsersStore()
  const { items, loading, loadingMore, nextCursor, filters } = storeToRefs(store)

  return {
    items,
    loading,
    loadingMore,
    nextCursor,
    filters,
    fetchFirst: (next?: UserListFilters) => store.fetchFirst(next),
    loadMore: () => store.loadMore(),
  }
}