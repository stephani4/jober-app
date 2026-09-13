<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { CustomDatepicker } from '@/components/CustomDatepicker'
import { useAuth } from '@/composables'
import { realtimeService } from '@/services/RealtimeService'
import type { WorkingArea } from '@/services/WorkingAreaService'

const router = useRouter()
const { user } = useAuth()

const areas = ref<WorkingArea[]>([])
const loading = ref(false)
const busy = ref(false)
const error = ref('')

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
    const payload = {
      name: form.value.name.trim(),
      birth_date: form.value.birth_date || null,
      working_area_id: form.value.working_area_id,
    }

    const updatedUser = await realtimeService.updateProfile(payload)

    // Обновляем данные в auth-сторе, чтобы профиль и hero-заголовок были актуальны.
    if (updatedUser && user.value) {
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
        <!-- Имя -->
        <div>
          <label class="text-sm text-text-secondary" for="profile-name">Имя</label>
          <input
            id="profile-name"
            v-model="form.name"
            type="text"
            autocomplete="name"
            placeholder="Ваше имя"
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
