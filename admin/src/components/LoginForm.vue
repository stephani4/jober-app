<script setup lang="ts">
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { useLoginForm } from '@/composables'

const emit = defineEmits<{
  success: []
}>()

const { form, v$, error, loading, canSubmit, firstError, submit } = useLoginForm()

async function onSubmit(): Promise<void> {
  if (await submit()) {
    emit('success')
  }
}
</script>

<template>
  <form
    class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-zinc-900"
    @submit.prevent="onSubmit"
  >
    <div class="space-y-2">
      <label class="block text-sm text-slate-600 dark:text-slate-300" for="login">Email</label>
      <InputText
        id="login"
        v-model="form.login"
        fluid
        autocomplete="username"
        @blur="v$.login.$touch()"
      />
      <p v-if="v$.login.$error" class="text-sm text-accent-danger">
        {{ firstError(v$.login.$errors) }}
      </p>
    </div>
    <div class="space-y-2">
      <label class="block text-sm text-slate-600 dark:text-slate-300" for="password">Пароль</label>
      <InputText
        id="password"
        v-model="form.password"
        type="password"
        fluid
        autocomplete="current-password"
        @blur="v$.password.$touch()"
      />
      <p v-if="v$.password.$error" class="text-sm text-accent-danger">
        {{ firstError(v$.password.$errors) }}
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
      class="w-full"
      :label="loading ? 'Входим…' : 'Войти'"
      :loading="loading"
      :disabled="!canSubmit"
    />
  </form>
</template>
