<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { isAxiosError } from 'axios'
import { useAuth } from '@/composables'
import { realtimeService } from '@/services/RealtimeService'
import { type WorkingArea } from '@/services/WorkingAreaService'

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

onMounted(async () => {
  loading.value = true
  try {
    // Load available working areas via WebSocket RPC
    areas.value = await realtimeService.listWorkingAreas()

    // Load current user data
    if (user.value) {
      form.value.name = user.value.name || ''
      form.value.working_area_id = user.value.working_area_id || null
      form.value.birth_date = user.value.birth_date || ''
    } else {
      throw new Error('Пользователь не авторизован')
    }
  } catch (err: any) {
    error.value = err.message || 'Ошибка при загрузке данных.'
  } finally {
    loading.value = false
  }
})
async function onSubmit() {
  error.value = ''
  busy.value = true
  try {
    // Update profile via WebSocket RPC
    const updatedUser = await realtimeService.updateProfile(form.value)
    
    // Update user data in auth store to keep it in sync
    if (updatedUser && user.value) {
      Object.assign(user.value, updatedUser)
    }

    router.push({ name: 'profile' })
  } catch (err: any) {
    error.value = err.message || 'Произошла ошибка при обновлении профиля.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Редактирование профиля</h1>
        <p class="mt-1 text-sm text-text-secondary">Обновите ваши личные данные и рабочую зону.</p>
      </div>
      <button
        type="button"
        class="rounded-lg border border-border-subtle px-4 py-2 text-sm hover:bg-zinc-50 dark:border-white/10 dark:hover:bg-zinc-800"
        @click="router.back()"
      >
        Отмена
      </button>
    </div>

    <p v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>

    <div v-if="loading" class="text-center py-10 text-sm text-text-secondary">
      Загрузка данных…
    </div>

    <form v-else class="space-y-4">
      <div class="space-y-2">
        <label class="text-sm font-medium">Имя</label>
        <input
          v-model="form.name"
          type="text"
          class="w-full rounded-lg border border-border-subtle bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-white/10"
          placeholder="Ваше имя"
        />
      </div>

      <div class="space-y-2">
        <label class="text-sm font-medium">Дата рождения</label>
        <input
          v-model="form.birth_date"
          type="date"
          class="w-full rounded-lg border border-border-subtle bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-white/10"
        />
      </div>

      <div class="space-y-2">
        <label class="text-sm font-medium">Рабочая зона</label>
        <select
          v-model="form.working_area_id"
          class="w-full rounded-lg border border-border-subtle bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-white/10"
        >
          <option :value="null">Не выбрана</option>
          <option v-for="area in areas" :key="area.id" :value="area.id">
            {{ area.name }}
          </option>
        </select>
      </div>

      <div class="pt-4">
        <button
          type="button"
          class="w-full rounded-lg bg-zinc-900 py-3 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
          :disabled="busy"
          @click="onSubmit"
        >
          {{ busy ? 'Сохранение…' : 'Сохранить изменения' }}
        </button>
      </div>
    </form>
  </section>
</template>
