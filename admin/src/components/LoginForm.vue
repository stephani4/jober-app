<script setup lang="ts">
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
      <input
        id="login"
        v-model="form.login"
        type="text"
        autocomplete="username"
        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none focus:border-zinc-800 dark:border-slate-700 dark:bg-zinc-950 dark:text-zinc-100"
        @blur="v$.login.$touch()"
      />
      <p v-if="v$.login.$error" class="text-sm text-accent-danger">
        {{ firstError(v$.login.$errors) }}
      </p>
    </div>
    <div class="space-y-2">
      <label class="block text-sm text-slate-600 dark:text-slate-300" for="password">Пароль</label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        autocomplete="current-password"
        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none focus:border-zinc-800 dark:border-slate-700 dark:bg-zinc-950 dark:text-zinc-100"
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
    <button
      type="submit"
      :disabled="!canSubmit"
      class="w-full rounded-xl bg-zinc-900 px-4 py-3 text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
    >
      {{ loading ? 'Входим…' : 'Войти' }}
    </button>
  </form>
</template>
