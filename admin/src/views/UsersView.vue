<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { isAxiosError } from 'axios'
import { useAdminUsers } from '@/composables'
import { userRoleLabel, type UserRole } from '@/schemas/user'

const { items, loading, loadingMore, nextCursor, fetchFirst, loadMore } = useAdminUsers()

const form = reactive({
  name: '',
  email: '',
  birth_date: '',
  role: '' as UserRole | '',
})

const error = ref('')

onMounted(() => {
  void fetchFirst()
})

async function onApply(): Promise<void> {
  error.value = ''
  try {
    await fetchFirst({
      name: form.name.trim() || undefined,
      email: form.email.trim() || undefined,
      birth_date: form.birth_date || undefined,
      role: form.role || undefined,
    })
  } catch (err) {
    error.value = extractError(err, 'Не удалось загрузить пользователей.')
  }
}

function onReset(): void {
  form.name = ''
  form.email = ''
  form.birth_date = ''
  form.role = ''
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
      <label class="flex flex-col gap-1 text-sm">
        <span class="text-text-secondary">Имя</span>
        <input
          v-model="form.name"
          type="text"
          placeholder="Иван"
          class="w-48 rounded-lg border border-border-subtle bg-white px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
        >
      </label>
      <label class="flex flex-col gap-1 text-sm">
        <span class="text-text-secondary">Email</span>
        <input
          v-model="form.email"
          type="text"
          placeholder="ivan@example.com"
          class="w-56 rounded-lg border border-border-subtle bg-white px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
        >
      </label>
      <label class="flex flex-col gap-1 text-sm">
        <span class="text-text-secondary">Дата рождения</span>
        <input
          v-model="form.birth_date"
          type="date"
          class="rounded-lg border border-border-subtle bg-white px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
        >
      </label>
      <label class="flex flex-col gap-1 text-sm">
        <span class="text-text-secondary">Роль</span>
        <select
          v-model="form.role"
          class="rounded-lg border border-border-subtle bg-white px-3 py-2 dark:border-white/10 dark:bg-zinc-900"
        >
          <option value="">Все</option>
          <option value="customer">Заказчик</option>
          <option value="executor">Исполнитель</option>
        </select>
      </label>
      <button
        type="submit"
        class="rounded-lg bg-zinc-900 px-4 py-2 text-sm text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
        :disabled="loading"
      >
        Применить
      </button>
      <button
        type="button"
        class="rounded-lg border border-border-subtle px-4 py-2 text-sm dark:border-white/10"
        :disabled="loading"
        @click="onReset"
      >
        Сбросить
      </button>
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

    <button
      v-if="nextCursor"
      type="button"
      class="rounded-xl border border-border-subtle px-4 py-2 text-sm dark:border-white/10"
      :disabled="loadingMore"
      @click="loadMore"
    >
      {{ loadingMore ? 'Загружаем…' : 'Ещё' }}
    </button>
  </section>
</template>