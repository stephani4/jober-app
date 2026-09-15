<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { isAxiosError } from 'axios'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import { useAdminUsers } from '@/composables'
import { userRoleLabel, type UserRole } from '@/schemas/user'

const { items, loading, loadingMore, nextCursor, fetchFirst, loadMore } = useAdminUsers()

const form = reactive({
  name: '',
  email: '',
  birth_date: null as Date | null,
  role: null as UserRole | null,
})

/** Опции фильтра «Роль» (первая опция — «все роли»). */
const roleOptions: { label: string; value: UserRole | null }[] = [
  { label: 'Все роли', value: null },
  { label: userRoleLabel.customer, value: 'customer' },
  { label: userRoleLabel.executor, value: 'executor' },
]

/** Дата рождения не может быть в будущем. */
const maxBirthDate = new Date()

const error = ref('')

onMounted(() => {
  void fetchFirst()
})

/** Date из DatePicker → строка 'YYYY-MM-DD' для API. */
function dateToKey(value: Date | null): string | undefined {
  if (!value) {
    return undefined
  }
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${value.getFullYear()}-${month}-${day}`
}

async function onApply(): Promise<void> {
  error.value = ''
  try {
    await fetchFirst({
      name: form.name.trim() || undefined,
      email: form.email.trim() || undefined,
      birth_date: dateToKey(form.birth_date),
      role: form.role ?? undefined,
    })
  } catch (err) {
    error.value = extractError(err, 'Не удалось загрузить пользователей.')
  }
}

function onReset(): void {
  form.name = ''
  form.email = ''
  form.birth_date = null
  form.role = null
  void onApply()
}

function extractError(err: unknown, fallback: string): string {
  if (isAxiosError(err)) {
    return (
      err.response?.data?.message
      || err.response?.data?.errors?.role?.[0]
      || err.response?.data?.errors?.birth_date?.[0]
      || fallback
    )
  }
  return fallback
}
</script>

<template>
  <section class="space-y-5">
    <div>
      <h1 class="text-2xl font-semibold">Пользователи</h1>
      <p class="mt-1 text-sm text-text-secondary">Пользователи приложения. Страница — 15 записей.</p>
    </div>

    <form
      class="flex flex-wrap items-end gap-3"
      @submit.prevent="onApply"
    >
      <label class="flex w-48 flex-col gap-1 text-sm" for="filter-user-name">
        <span class="text-text-secondary">Имя</span>
        <InputText
          v-model="form.name"
          id="filter-user-name"
          fluid
          placeholder="Иван"
        />
      </label>
      <label class="flex w-56 flex-col gap-1 text-sm" for="filter-user-email">
        <span class="text-text-secondary">Email</span>
        <InputText
          v-model="form.email"
          id="filter-user-email"
          fluid
          placeholder="ivan@example.com"
        />
      </label>
      <label class="flex w-44 flex-col gap-1 text-sm" for="filter-user-birth-date">
        <span class="text-text-secondary">Дата рождения</span>
        <DatePicker
          v-model="form.birth_date"
          input-id="filter-user-birth-date"
          date-format="yy-mm-dd"
          placeholder="ГГГГ-ММ-ДД"
          show-icon
          fluid
          :max-date="maxBirthDate"
        />
      </label>
      <label class="flex w-44 flex-col gap-1 text-sm" for="filter-user-role">
        <span class="text-text-secondary">Роль</span>
        <Select
          v-model="form.role"
          input-id="filter-user-role"
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
      Загружаем пользователей…
    </p>
    <p
      v-else-if="items.length === 0"
      class="text-sm text-text-secondary"
    >
      Пользователи не найдены.
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
            <th class="px-4 py-3 font-medium">Дата рождения</th>
            <th class="px-4 py-3 font-medium">Роль</th>
            <th class="px-4 py-3 font-medium">Регистрация</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="user in items"
            :key="user.id"
            class="border-b border-border-subtle last:border-0 dark:border-white/10"
          >
            <td class="px-4 py-3 text-text-secondary">{{ user.id }}</td>
            <td class="px-4 py-3">{{ user.name }}</td>
            <td class="px-4 py-3">{{ user.email }}</td>
            <td class="px-4 py-3 text-text-secondary">{{ user.birth_date || '—' }}</td>
            <td class="px-4 py-3">
              <span
                v-if="user.role"
                class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs dark:bg-white/10"
              >
                {{ user.role_label || userRoleLabel[user.role] }}
              </span>
              <span v-else class="text-text-secondary">—</span>
            </td>
            <td class="px-4 py-3 text-text-secondary">
              {{ user.created_at ? new Date(user.created_at).toLocaleDateString('ru-RU') : '—' }}
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