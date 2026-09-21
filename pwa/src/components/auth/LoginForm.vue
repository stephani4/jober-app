<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import AppBrandLogo from '@/components/AppBrandLogo.vue'
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

const showPassword = ref(false)

async function onSubmit(): Promise<void> {
  const redirect = await submit()
  if (redirect) {
    emit('success', redirect)
  }
}
</script>

<template>
  <form
    class="w-full rounded-3xl bg-white p-6 sm:p-10 dark:bg-slate-900 dark:shadow-black/30 dark:ring-1 dark:ring-slate-700"
    @submit.prevent="onSubmit"
  >
    <!-- Шапка карточки: логотип, заголовок, подзаголовок -->
    <div class="h-10 w-fit sm:h-12">
      <AppBrandLogo />
    </div>
    <h2 class="mt-5 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
      С возвращением в Делег
    </h2>
    <p class="mt-2 text-sm text-text-secondary dark:text-slate-400">
      Войдите, чтобы смотреть и брать заказы
    </p>

    <p
      v-if="info"
      class="mt-5 rounded-2xl border border-accent-primary/25 bg-accent-primary/10 px-4 py-3 text-sm text-text-primary dark:text-text-primary"
    >
      {{ info }}
    </p>

    <div class="mt-8 space-y-4">
      <div>
        <label class="sr-only" for="login">Логин</label>
        <div class="relative">
          <span aria-hidden="true" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <rect x="3" y="5" width="18" height="14" rx="3" />
              <path d="m4 7 8 6 8-6" />
            </svg>
          </span>
          <input
            id="login"
            v-model="form.login"
            type="text"
            autocomplete="username"
            placeholder="email@example.com"
            class="w-full rounded-full border border-border-input bg-white py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-accent-primary focus:ring-2 focus:ring-accent-primary/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-accent-primary"
            @blur="v$.login.$touch()"
          />
        </div>
        <p v-if="v$.login.$error" class="mt-2 pl-4 text-sm text-rose-600 dark:text-rose-300">
          {{ firstError(v$.login.$errors) }}
        </p>
      </div>

      <div>
        <label class="sr-only" for="password">Пароль</label>
        <div class="relative">
          <span aria-hidden="true" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <rect x="4" y="10" width="16" height="10" rx="3" />
              <path d="M8 10V7a4 4 0 0 1 8 0v3" />
            </svg>
          </span>
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            placeholder="Пароль"
            class="w-full rounded-full border border-border-input bg-white py-3.5 pl-12 pr-12 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-accent-primary focus:ring-2 focus:ring-accent-primary/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-accent-primary"
            @blur="v$.password.$touch()"
          />
          <button
            type="button"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-300"
            :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
            @click="showPassword = !showPassword"
          >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12Z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </button>
        </div>
        <p v-if="v$.password.$error" class="mt-2 pl-4 text-sm text-rose-600 dark:text-rose-300">
          {{ firstError(v$.password.$errors) }}
        </p>
      </div>
    </div>

    <p
      v-if="error"
      class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200"
    >
      {{ error }}
    </p>

    <button
      type="submit"
      :disabled="!canSubmit"
      class="mt-6 w-full rounded-full bg-accent-primary py-3.5 font-medium text-white transition hover:bg-accent-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
    >
      {{ loading ? 'Входим…' : 'Войти' }}
    </button>

    <div class="mt-6 flex items-center gap-4" aria-hidden="true">
      <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
      <span class="text-xs text-slate-400 dark:text-slate-500">или</span>
      <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
    </div>

    <p class="mt-5 text-center text-sm text-slate-500 dark:text-slate-400">
      Нет аккаунта?
      <RouterLink
        class="font-medium text-accent-primary underline-offset-4 hover:underline"
        :to="{ name: 'register' }"
      >
        Зарегистрироваться
      </RouterLink>
    </p>
  </form>
</template>
