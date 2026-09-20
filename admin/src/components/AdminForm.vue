<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import MultiSelect from 'primevue/multiselect'
import { useAdminForm } from '@/composables'

const props = defineProps<{
  isEdit: boolean
  adminId: number | null
}>()

const emit = defineEmits<{
  success: []
}>()

const {
  form,
  catalog,
  v$,
  error,
  loading,
  loadingRecord,
  canSubmit,
  firstError,
  load,
  submit,
} = useAdminForm(
  () => props.isEdit,
  () => props.adminId,
)

void load()

async function onSubmit(): Promise<void> {
  if (await submit()) {
    emit('success')
  }
}
</script>

<template>
  <form
    class="max-w-xl space-y-5 rounded-2xl border border-border-subtle bg-white p-6 dark:border-white/10 dark:bg-zinc-900"
    @submit.prevent="onSubmit"
  >
    <p
      v-if="loadingRecord"
      class="text-sm text-text-secondary"
    >
      Загружаем сотрудника…
    </p>

    <div class="space-y-2">
      <label class="block text-sm text-text-secondary" for="admin-name">Имя</label>
      <InputText
        id="admin-name"
        v-model="form.name"
        fluid
        autocomplete="name"
        :disabled="loadingRecord"
        @blur="v$.name.$touch()"
      />
      <p v-if="v$.name.$error" class="text-sm text-accent-danger">
        {{ firstError(v$.name.$errors) }}
      </p>
    </div>

    <div class="space-y-2">
      <label class="block text-sm text-text-secondary" for="admin-email">Email</label>
      <InputText
        id="admin-email"
        v-model="form.email"
        fluid
        type="email"
        autocomplete="username"
        :disabled="loadingRecord"
        @blur="v$.email.$touch()"
      />
      <p v-if="v$.email.$error" class="text-sm text-accent-danger">
        {{ firstError(v$.email.$errors) }}
      </p>
    </div>

    <div class="space-y-2">
      <label class="block text-sm text-text-secondary" for="admin-roles">Роли</label>
      <MultiSelect
        v-model="form.roles"
        input-id="admin-roles"
        :options="catalog.roles"
        option-label="label"
        option-value="value"
        placeholder="Выберите роли"
        display="chip"
        filter
        fluid
        :disabled="loadingRecord"
        @blur="v$.roles.$touch()"
      />
      <p class="text-xs text-text-secondary">
        Можно выбрать несколько ролей одновременно.
      </p>
      <p v-if="v$.roles.$error" class="text-sm text-accent-danger">
        {{ firstError(v$.roles.$errors) }}
      </p>
    </div>

    <div class="space-y-2">
      <label class="block text-sm text-text-secondary" for="admin-permissions">Разрешения</label>
      <MultiSelect
        v-model="form.permissions"
        input-id="admin-permissions"
        :options="catalog.permissions"
        option-label="label"
        option-value="value"
        placeholder="Дополнительные разрешения"
        display="chip"
        filter
        fluid
        :disabled="loadingRecord"
      />
      <p class="text-xs text-text-secondary">
        Отдельно от ролей: список всех разрешений. Выбранные права выдаются сотруднику напрямую.
      </p>
    </div>

    <div class="space-y-2">
      <label class="block text-sm text-text-secondary" for="admin-password">
        {{ isEdit ? 'Новый пароль' : 'Пароль' }}
      </label>
      <InputText
        id="admin-password"
        v-model="form.password"
        type="password"
        fluid
        autocomplete="new-password"
        :disabled="loadingRecord"
        @blur="v$.password.$touch()"
      />
      <p v-if="isEdit" class="text-xs text-text-secondary">
        Оставьте пустым, чтобы не менять пароль.
      </p>
      <p v-if="v$.password.$error" class="text-sm text-accent-danger">
        {{ firstError(v$.password.$errors) }}
      </p>
    </div>

    <div class="space-y-2">
      <label class="block text-sm text-text-secondary" for="admin-password-confirmation">
        Подтверждение пароля
      </label>
      <InputText
        id="admin-password-confirmation"
        v-model="form.password_confirmation"
        type="password"
        fluid
        autocomplete="new-password"
        :disabled="loadingRecord"
        @blur="v$.password_confirmation.$touch()"
      />
      <p v-if="v$.password_confirmation.$error" class="text-sm text-accent-danger">
        {{ firstError(v$.password_confirmation.$errors) }}
      </p>
    </div>

    <p
      v-if="error"
      class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-400/20 dark:bg-rose-950/40 dark:text-rose-200"
    >
      {{ error }}
    </p>

    <Button
      type="submit"
      :label="loading ? 'Сохраняем…' : 'Сохранить'"
      :loading="loading"
      :disabled="!canSubmit"
    />
  </form>
</template>
