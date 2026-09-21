<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { CustomDatepicker } from '@/components/CustomDatepicker'
import AvatarPicker from '@/components/profile/AvatarPicker.vue'
import { useAuth } from '@/composables'
import { realtimeService } from '@/services/RealtimeService'
import type { ProfileUpdatePayload } from '@/schemas/user'
import type { WorkingAreaOption } from '@/schemas/workingArea'

const router = useRouter()
const { user } = useAuth()

const areas = ref<WorkingAreaOption[]>([])
const loading = ref(false)
const busy = ref(false)
const error = ref('')

// Аватар: id файла уйдёт в users.avatar_id, url нужен только для превью.
const avatarId = ref<number | null>(null)
const avatarUrl = ref<string | null>(null)

const form = ref({
  working_area_id: null as number | null,
  name: '',
  birth_date: '',
})

// Данные подгружаются через Centrifugo RPC (profile:areas) — как на регистрации.
onMounted(async () => {
  loading.value = true
  try {
    areas.value = await realtimeService.listWorkingAreas()

    if (!user.value) {
      throw new Error('Пользователь не авторизован')
    }

    form.value.name = user.value.name || ''
    form.value.working_area_id = user.value.working_area_id || null
    form.value.birth_date = user.value.birth_date || ''
    avatarId.value = user.value.avatar_id ?? null
    avatarUrl.value = user.value.avatar_url ?? null
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Ошибка при загрузке данных.'
  } finally {
    loading.value = false
  }
})

async function onSubmit(): Promise<void> {
  error.value = ''
  busy.value = true
  try {
    // Пустую дату шлём как null: бэкенд-валидация 'date' не принимает пустую строку.
    const payload: ProfileUpdatePayload = {
      name: form.value.name.trim(),
      birth_date: form.value.birth_date || null,
      working_area_id: form.value.working_area_id,
      avatar_id: avatarId.value,
    }

    const updatedUser = await realtimeService.updateProfile(payload)

    // Обновляем данные в auth-сторе, чтобы профиль и hero-заголовок были актуальны.
    if (user.value) {
      Object.assign(user.value, updatedUser)
    }

    router.push({ name: 'profile' })
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Произошла ошибка при обновлении профиля.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="space-y-3">
    <p
      v-if="error"
      class="rounded-card border border-accent-danger/40 bg-red-50 px-4 py-3 text-sm text-accent-danger dark:border-red-500/40 dark:bg-red-950/40 dark:text-red-300"
    >
      {{ error }}
    </p>

    <p v-if="loading" class="py-10 text-center text-sm text-text-secondary">
      Загрузка данных…
    </p>

    <form v-else class="space-y-3" @submit.prevent="onSubmit">
      <div
        class="rounded-card border border-border-subtle bg-surface-card p-4 shadow-[var(--shadow-card)] dark:border-white/10 dark:bg-zinc-900"
      >
        <!-- Аватар: файл загружается сразу, users.avatar_id присваивается при сохранении -->
        <AvatarPicker
          v-model="avatarId"
          v-model:url="avatarUrl"
          :name="user?.name"
        />

        <!-- ФИО -->
        <div class="mt-4">
          <label class="text-sm text-text-secondary" for="profile-name">ФИО</label>
          <input
            id="profile-name"
            v-model="form.name"
            type="text"
            autocomplete="name"
            placeholder="Иванов Иван Иванович"
            class="mt-1 w-full rounded-xl border border-border-subtle bg-white px-4 py-3 text-text-primary outline-none transition placeholder:text-text-secondary/60 focus:border-accent-nav focus:ring-2 focus:ring-accent-nav/25 dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
          />
        </div>

        <!-- Дата рождения: PrimeVue DatePicker, как на регистрации -->
        <div class="mt-4">
          <label class="text-sm text-text-secondary" for="profile-birth-date">Дата рождения</label>
          <CustomDatepicker
            v-model="form.birth_date"
            input-id="profile-birth-date"
            :max-date="new Date()"
            class="mt-1 w-full"
          />
        </div>

        <!-- Рабочая зона -->
        <div class="mt-4">
          <label class="text-sm text-text-secondary" for="profile-area">Рабочая зона</label>
          <select
            id="profile-area"
            v-model="form.working_area_id"
            class="mt-1 w-full rounded-xl border border-border-subtle bg-white px-4 py-3 text-text-primary outline-none transition focus:border-accent-nav focus:ring-2 focus:ring-accent-nav/25 dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-100"
          >
            <option :value="null">Не выбрана</option>
            <option v-for="area in areas" :key="area.id" :value="area.id">
              {{ area.name }}
            </option>
          </select>
        </div>
      </div>

      <button
        type="submit"
        :disabled="busy"
        class="w-full rounded-full bg-accent-nav px-4 py-3 text-white transition hover:bg-accent-nav-hover disabled:cursor-not-allowed disabled:opacity-50"
      >
        {{ busy ? 'Сохранение…' : 'Сохранить изменения' }}
      </button>
    </form>
  </section>
</template>
