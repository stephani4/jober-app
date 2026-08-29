<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { useLoginForm } from '@/composables'

const props = defineProps<{
  info?: string
  redirectTo?: string
}>()

const emit = defineEmits<{
  success: [redirectTo: string]
}>()

const { form, v$, error, loading, canSubmit, firstError, submit } = useLoginForm({
  redirectTo: props.redirectTo,
})

async function onSubmit(): Promise<void> {
  const redirect = await submit()
  if (redirect) {
    emit('success', redirect)
  }
}
</script>

<template>
  <form
    class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/30"
    @submit.prevent="onSubmit"
  >
    <p
      v-if="info"
      class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800 dark:border-teal-400/20 dark:bg-teal-500/10 dark:text-teal-100"
    >
      {{ info }}
    </p>

    <div class="space-y-2">
      <label class="block text-sm text-slate-600 dark:text-slate-300" for="login">Логин</label>
      <input
        id="login"
        v-model="form.login"
        type="text"
        autocomplete="username"
        placeholder="email@example.com"
        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-teal-400/60"
        @blur="v$.login.$touch()"
      />
      <p v-if="v$.login.$error" class="text-sm text-rose-600 dark:text-rose-300">
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
        placeholder="••••••••"
        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-teal-400/60"
        @blur="v$.password.$touch()"
      />
      <p v-if="v$.password.$error" class="text-sm text-rose-600 dark:text-rose-300">
        {{ firstError(v$.password.$errors) }}
      </p>
    </div>

    <p
      v-if="error"
      class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200"
    >
      {{ error }}
    </p>

    <button
      type="submit"
      :disabled="!canSubmit"
      class="w-full rounded-xl bg-teal-600 px-4 py-3 font-medium text-white transition hover:bg-teal-500 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-teal-400 dark:text-slate-950 dark:hover:bg-teal-300"
    >
      {{ loading ? 'Входим…' : 'Войти' }}
    </button>

    <p class="text-center text-sm text-slate-500 dark:text-slate-400">
      Нет аккаунта?
      <RouterLink
        class="text-teal-700 underline-offset-4 hover:underline dark:text-teal-300"
        :to="{ name: 'register' }"
      >
        Зарегистрироваться
      </RouterLink>
    </p>
  </form>
</template>
