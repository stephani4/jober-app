import { computed, reactive, ref } from 'vue'
import { isAxiosError } from 'axios'
import useVuelidate from '@vuelidate/core'
import { adminAdministratorService } from '@/services/AdminAdministratorService'
import { adminRoleSchema, type AdminCatalog } from '@/schemas/admin'
import { adminFormValidationRules, firstVuelidateError } from '@/validations'
import { Permission } from '@/permissions'
import { ensurePermission, PermissionDeniedError } from '@/middleware/permission'

export interface AdminFormState {
  name: string
  email: string
  password: string
  password_confirmation: string
  roles: string[]
  permissions: string[]
}

/**
 * Форма создания и редактирования сотрудника админки.
 */
export function useAdminForm(isEdit: () => boolean, adminId: () => number | null) {
  const form = reactive<AdminFormState>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [],
    permissions: [],
  })

  const catalog = ref<AdminCatalog>({ roles: [], permissions: [] })

  const v$ = useVuelidate(
    computed(() => adminFormValidationRules(
      () => isEdit(),
      () => form.password,
    )),
    form,
  )
  const error = ref('')
  const loading = ref(false)
  const loadingRecord = ref(false)

  const canSubmit = computed(() => !loading.value && !loadingRecord.value)

  /**
   * Загружает карточку и справочник ролей/разрешений.
   */
  async function load(): Promise<boolean> {
    error.value = ''
    loadingRecord.value = true
    try {
      catalog.value = await adminAdministratorService.getCatalog()

      const id = adminId()
      if (!isEdit() || id == null) {
        return true
      }

      const admin = await adminAdministratorService.getById(id)
      form.name = admin.name
      form.email = admin.email
      form.password = ''
      form.password_confirmation = ''
      form.roles = admin.roles.filter((role) => adminRoleSchema.safeParse(role).success)
      form.permissions = [...admin.direct_permissions]
      return true
    } catch (err) {
      error.value = extractError(err, 'Не удалось загрузить сотрудника.')
      return false
    } finally {
      loadingRecord.value = false
    }
  }

  /**
   * Создаёт или обновляет сотрудника.
   */
  async function submit(): Promise<boolean> {
    error.value = ''
    const valid = await v$.value.$validate()
    if (!valid || form.roles.length === 0) {
      return false
    }

    loading.value = true
    try {
      const payload = {
        name: form.name.trim(),
        email: form.email.trim(),
        roles: form.roles,
        permissions: form.permissions,
        ...(form.password ? { password: form.password } : {}),
      }

      if (isEdit()) {
        const id = adminId()
        if (id == null) {
          return false
        }
        ensurePermission(Permission.AdminsUpdate)
        await adminAdministratorService.update(id, payload)
      } else {
        ensurePermission(Permission.AdminsCreate)
        await adminAdministratorService.create({
          ...payload,
          password: form.password,
        })
      }
      return true
    } catch (err) {
      error.value = extractError(err, 'Не удалось сохранить сотрудника.')
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    form,
    catalog,
    v$,
    error,
    loading,
    loadingRecord,
    canSubmit,
    firstError: firstVuelidateError,
    load,
    submit,
  }
}

function extractError(err: unknown, fallback: string): string {
  if (err instanceof PermissionDeniedError) {
    return err.message
  }
  if (!isAxiosError(err)) {
    return fallback
  }

  const data = err.response?.data
  return (
    data?.message
    || data?.errors?.email?.[0]
    || data?.errors?.password?.[0]
    || data?.errors?.roles?.[0]
    || data?.errors?.permissions?.[0]
    || data?.errors?.name?.[0]
    || fallback
  )
}
