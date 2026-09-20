<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { isAxiosError } from 'axios'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import { useAdmins, useAuth } from '@/composables'
import { Permission } from '@/permissions'
import { PermissionDeniedError } from '@/middleware/permission'
import { adminRoleLabel, formatAdminRoles, type Admin, type AdminRole } from '@/schemas/admin'

const router = useRouter()
const { admin: currentAdmin, can } = useAuth()
const { items, loading, loadingMore, nextCursor, fetchFirst, loadMore, deleteAdmin } = useAdmins()

const form = reactive({
  name: '',
  email: '',
  role: null as AdminRole | null,
})

/** Опции фильтра «Роль» (первая опция — «все роли»). */
const roleOptions: { label: string; value: AdminRole | null }[] = [
  { label: 'Все роли', value: null },
  { label: adminRoleLabel['super-admin'], value: 'super-admin' },
  { label: adminRoleLabel.moderator, value: 'moderator' },
  { label: adminRoleLabel.users, value: 'users' },
  { label: adminRoleLabel['admin-worker'], value: 'admin-worker' },
]

const error = ref('')
const deletingId = ref<number | null>(null)

onMounted(() => {
  void fetchFirst()
})

async function onApply(): Promise<void> {
  error.value = ''
  try {
    await fetchFirst({
      name: form.name.trim() || undefined,
      email: form.email.trim() || undefined,
      role: form.role ?? undefined,
    })
  } catch (err) {
    error.value = extractError(err, 'Не удалось загрузить администраторов.')
  }
}

function onReset(): void {
  form.name = ''
  form.email = ''
  form.role = null
  void onApply()
}

function canDelete(admin: Admin): boolean {
  return can(Permission.AdminsDelete) && currentAdmin.value?.id !== admin.id
}

async function onDelete(admin: Admin): Promise<void> {
  if (!canDelete(admin)) {
    return
  }
  if (!confirm(`Удалить администратора ${admin.email}?`)) {
    return
  }

  deletingId.value = admin.id
  error.value = ''
  try {
    await deleteAdmin(admin.id)
    await fetchFirst()
  } catch (err) {
    error.value = extractError(err, 'Не удалось удалить администратора.')
  } finally {
    deletingId.value = null
  }
}

function extractError(err: unknown, fallback: string): string {
  if (err instanceof PermissionDeniedError) {
    return err.message
  }
  if (isAxiosError(err)) {
    return (
      err.response?.data?.message
      || err.response?.data?.errors?.role?.[0]
      || err.response?.data?.errors?.id?.[0]
      || fallback
    )
  }
  return fallback
}
</script>

<template>
  <section class="space-y-5">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold">Администраторы</h1>
        <p class="mt-1 text-sm text-text-secondary">Сотрудники админки. Страница — 15 записей.</p>
      </div>
      <Button
        v-if="can(Permission.AdminsCreate)"
        label="Создать"
        @click="router.push({ name: 'admins-create' })"
      />
    </div>

    <form
      class="flex flex-wrap items-end gap-3"
      @submit.prevent="onApply"
    >
      <label class="flex w-48 flex-col gap-1 text-sm" for="filter-admin-name">
        <span class="text-text-secondary">Имя</span>
        <InputText
          v-model="form.name"
          id="filter-admin-name"
          fluid
          placeholder="Анна"
        />
      </label>
      <label class="flex w-56 flex-col gap-1 text-sm" for="filter-admin-email">
        <span class="text-text-secondary">Email</span>
        <InputText
          v-model="form.email"
          id="filter-admin-email"
          fluid
          placeholder="anna@example.com"
        />
      </label>
      <label class="flex w-44 flex-col gap-1 text-sm" for="filter-admin-role">
        <span class="text-text-secondary">Роль</span>
        <Select
          v-model="form.role"
          input-id="filter-admin-role"
          :options="roleOptions"
          option-label="label"
          option-value="value"
          placeholder="Все роли"
          fluid
        />
      </label>
      <Button
        type="submit"
        label="Применить"
        :loading="loading"
      />
      <Button
        type="button"
        label="Сбросить"
        severity="secondary"
        variant="outlined"
        :disabled="loading"
        @click="onReset"
      />
    </form>

    <p
      v-if="error"
      class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </p>
    <p
      v-if="loading"
      class="text-sm text-text-secondary"
    >
      Загружаем администраторов…
    </p>
    <p
      v-else-if="items.length === 0"
      class="text-sm text-text-secondary"
    >
      Администраторы не найдены.
    </p>

    <div
      v-else
      class="overflow-x-auto rounded-2xl border border-border-subtle bg-white dark:border-white/10 dark:bg-zinc-900"
    >
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-border-subtle text-text-secondary dark:border-white/10">
          <tr>
            <th class="px-4 py-3 font-medium">ID</th>
            <th class="px-4 py-3 font-medium">Имя</th>
            <th class="px-4 py-3 font-medium">Email</th>
            <th class="px-4 py-3 font-medium">Роль</th>
            <th class="px-4 py-3 font-medium">Создан</th>
            <th class="px-4 py-3 font-medium"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="admin in items"
            :key="admin.id"
            class="border-b border-border-subtle last:border-0 dark:border-white/10"
          >
            <td class="px-4 py-3 text-text-secondary">{{ admin.id }}</td>
            <td class="px-4 py-3">{{ admin.name }}</td>
            <td class="px-4 py-3">{{ admin.email }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs dark:bg-white/10">
                {{ formatAdminRoles(admin) || '—' }}
              </span>
            </td>
            <td class="px-4 py-3 text-text-secondary">
              {{ admin.created_at ? new Date(admin.created_at).toLocaleDateString('ru-RU') : '—' }}
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end gap-2">
                <Button
                  v-if="can(Permission.AdminsUpdate)"
                  size="small"
                  severity="secondary"
                  variant="outlined"
                  label="Редактировать"
                  @click="router.push({ name: 'admins-edit', params: { id: admin.id } })"
                />
                <Button
                  v-if="can(Permission.AdminsDelete)"
                  size="small"
                  severity="danger"
                  variant="outlined"
                  label="Удалить"
                  :disabled="!canDelete(admin)"
                  :loading="deletingId === admin.id"
                  @click="onDelete(admin)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Button
      v-if="nextCursor"
      variant="outlined"
      label="Ещё"
      :loading="loadingMore"
      @click="loadMore"
    />
  </section>
</template>
