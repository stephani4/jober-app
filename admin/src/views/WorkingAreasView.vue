<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { isAxiosError } from 'axios'
import Button from 'primevue/button'
import { workingAreaService, type WorkingArea } from '@/services/WorkingAreaService'

const router = useRouter()
const route = useRoute()
const items = ref<WorkingArea[]>([])
const loading = ref(false)
const error = ref('')

async function fetchAreas() {
  loading.value = true
  error.value = ''
  try {
    items.value = await workingAreaService.list()
  } catch (err) {
    error.value = isAxiosError(err)
      ? err.response?.data?.message || 'Не удалось загрузить рабочие зоны.'
      : 'Произошла ошибка при загрузке.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchAreas)

async function onDelete(id: number) {
  if (!confirm('Вы уверены, что хотите удалить эту зону?')) return
  
  try {
    await workingAreaService.delete(id)
    await fetchAreas()
  } catch (err) {
    error.value = isAxiosError(err)
      ? err.response?.data?.message || 'Не удалось удалить зону.'
      : 'Произошла ошибка.'
  }
}
</script>

<template>
  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Рабочие зоны</h1>
        <p class="mt-1 text-sm text-text-secondary">Управление географическими областями работы.</p>
      </div>
      <Button
        label="Создать зону"
        @click="router.push({ name: 'working-areas-create' })"
      />
    </div>

    <p v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>
    
    <div v-if="loading" class="text-sm text-text-secondary">Загрузка…</div>
    <div v-else-if="items.length === 0" class="text-sm text-text-secondary">Рабочие зоны не найдены.</div>

    <div v-else class="overflow-x-auto rounded-2xl border border-border-subtle bg-white dark:border-white/10 dark:bg-zinc-900">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-border-subtle text-text-secondary dark:border-white/10">
          <tr>
            <th class="px-4 py-3 font-medium">ID</th>
            <th class="px-4 py-3 font-medium">Название</th>
            <th class="px-4 py-3 font-medium">Тип</th>
            <th class="px-4 py-3 font-medium">Геометрия</th>
            <th class="px-4 py-3 font-medium"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="area in items"
            :key="area.id"
            class="border-b border-border-subtle align-top last:border-0 dark:border-white/10"
          >
            <td class="px-4 py-3 text-text-secondary">{{ area.id }}</td>
            <td class="px-4 py-3 font-medium">{{ area.name }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs dark:bg-zinc-800">
                {{ area.type === 'city' ? 'Город' : 'Другое' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="max-w-xs truncate text-xs text-text-secondary" :title="area.points">
                {{ area.points || 'Нет данных' }}
              </div>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end gap-2">
                <Button
                  size="small"
                  severity="secondary"
                  variant="outlined"
                  label="Редактировать"
                  @click="router.push({ name: 'working-areas-edit', params: { id: area.id } })"
                />
                <Button
                  size="small"
                  severity="danger"
                  variant="outlined"
                  label="Удалить"
                  @click="onDelete(area.id)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
