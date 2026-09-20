import { storeToRefs } from 'pinia'
import { useAdminsStore } from '@/stores/admins'
import { adminAdministratorService, type AdminListFilters } from '@/services/AdminAdministratorService'
import { Permission } from '@/permissions'
import { withPermission } from '@/middleware/permission'

/**
 * Список сотрудников админки с фильтрами и пагинацией.
 */
export function useAdmins() {
  const store = useAdminsStore()
  const { items, loading, loadingMore, nextCursor, filters } = storeToRefs(store)

  return {
    items,
    loading,
    loadingMore,
    nextCursor,
    filters,
    fetchFirst: (next?: AdminListFilters) => store.fetchFirst(next),
    loadMore: () => store.loadMore(),
    deleteAdmin: withPermission(Permission.AdminsDelete, (id: number) => adminAdministratorService.delete(id)),
  }
}
