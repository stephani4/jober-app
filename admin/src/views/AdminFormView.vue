<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import AdminForm from '@/components/AdminForm.vue'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => route.name === 'admins-edit')
const adminId = computed(() => {
  const raw = route.params.id
  const value = Array.isArray(raw) ? raw[0] : raw
  const id = Number(value)
  return Number.isFinite(id) && id > 0 ? id : null
})

function onSuccess(): void {
  void router.push({ name: 'admins' })
}
</script>

<template>
  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">
          {{ isEdit ? 'Редактирование администратора' : 'Создание администратора' }}
        </h1>
        <p class="mt-1 text-sm text-text-secondary">
          Имя, email, роль и пароль сотрудника админки.
        </p>
      </div>
      <Button
        severity="secondary"
        variant="outlined"
        label="К списку"
        @click="router.push({ name: 'admins' })"
      />
    </div>

    <AdminForm
      :key="`${isEdit}-${adminId}`"
      :is-edit="isEdit"
      :admin-id="adminId"
      @success="onSuccess"
    />
  </section>
</template>
