<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { useRegisterForm } from '@/composables'
import { CustomDatepicker } from '@/components/CustomDatepicker'

const emit = defineEmits<{
  success: []
}>()

const { form, v$, error, loading, canSubmit, clientError, submit } = useRegisterForm()

async function onSubmit(): Promise<void> {
  const ok = await submit()
  if (ok) {
    emit('success')
  }
}
</script>

<template>
  <form
    class="w-full rounded-3xl bg-white p-6 sm:p-10 dark:bg-slate-900 dark:shadow-black/30 dark:ring-1 dark:ring-slate-700"
    @submit.prevent="onSubmit"
  >
    <!-- Шапка карточки: логотип, заголовок, подзаголовок -->
    <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">Jober</p>
    <h2 class="mt-5 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
      Создайте аккаунт
    </h2>
    <p class="mt-2 text-sm text-text-secondary dark:text-slate-400">
      Регистрация заказчика или исполнителя
    </p>

    <div class="mt-8 space-y-4">
      <!-- ФИО -->
      <div>
        <label class="sr-only" for="name">ФИО</label>
        <div class="relative">
          <span aria-hidden="true" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <circle cx="12" cy="8" r="4" />
              <path d="M4 20c0-4 3.6-6 8-6s8 2 8 6" />
            </svg>
          </span>
          <input
            id="name"
            v-model="form.name"
            type="text"
            autocomplete="name"
            placeholder="Иванов Иван Иванович"
            class="w-full rounded-full border border-border-input bg-white py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-accent-primary focus:ring-2 focus:ring-accent-primary/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-accent-primary"
            @blur="v$.name.$touch()"
          />
        </div>
        <p v-if="clientError('name')" class="mt-2 pl-4 text-sm text-rose-600 dark:text-rose-300">
          {{ clientError('name') }}
        </p>
      </div>

      <!-- Email -->
      <div>
        <label class="sr-only" for="email">Email</label>
        <div class="relative">
          <span aria-hidden="true" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <rect x="3" y="5" width="18" height="14" rx="3" />
              <path d="m4 7 8 6 8-6" />
            </svg>
          </span>
          <input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            placeholder="email@example.com"
            class="w-full rounded-full border border-border-input bg-white py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-accent-primary focus:ring-2 focus:ring-accent-primary/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-accent-primary"
            @blur="v$.email.$touch()"
          />
        </div>
        <p v-if="clientError('email')" class="mt-2 pl-4 text-sm text-rose-600 dark:text-rose-300">
          {{ clientError('email') }}
        </p>
      </div>
      <!-- Пароли -->
      <div class="grid gap-4 sm:grid-cols-2">
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
              type="password"
              autocomplete="new-password"
              placeholder="Пароль"
              class="w-full rounded-full border border-border-input bg-white py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-accent-cyan focus:ring-2 focus:ring-accent-cyan/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-accent-cyan"
              @blur="v$.password.$touch()"
            />
          </div>
          <p v-if="clientError('password')" class="mt-2 pl-4 text-sm text-rose-600 dark:text-rose-300">
            {{ clientError('password') }}
          </p>
        </div>
        <div>
          <label class="sr-only" for="password_confirmation">Повтор пароля</label>
          <div class="relative">
            <span aria-hidden="true" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <rect x="4" y="10" width="16" height="10" rx="3" />
                <path d="M8 10V7a4 4 0 0 1 8 0v3" />
              </svg>
            </span>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              placeholder="Повтор пароля"
              class="w-full rounded-full border border-border-input bg-white py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-accent-cyan focus:ring-2 focus:ring-accent-cyan/25 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-accent-cyan"
              @blur="v$.password_confirmation.$touch()"
            />
          </div>
          <p
            v-if="clientError('password_confirmation')"
            class="mt-2 pl-4 text-sm text-rose-600 dark:text-rose-300"
          >
            {{ clientError('password_confirmation') }}
          </p>
        </div>
      </div>

      <!-- Дата рождения -->
      <div>
        <label class="sr-only" for="birth_date">Дата рождения</label>
        <CustomDatepicker
          v-model="form.birth_date"
          input-id="birth_date"
          :max-date="new Date()"
          class="w-full"
          @blur="v$.birth_date.$touch()"
        />
        <p v-if="clientError('birth_date')" class="mt-2 pl-4 text-sm text-rose-600 dark:text-rose-300">
          {{ clientError('birth_date') }}
        </p>
      </div>
    </div>

    <!-- Роль -->
    <fieldset class="mt-6">
      <legend class="text-sm font-medium text-slate-700 dark:text-slate-300">Роль</legend>
      <div class="mt-3 grid gap-3 sm:grid-cols-2">
        <label
          class="cursor-pointer rounded-full border px-4 py-3 text-center transition"
          :class="
            form.role === 'customer'
              ? 'border-accent-primary bg-accent-primary/10 text-slate-900 dark:text-slate-100 dark:bg-accent-primary/20 dark:border-accent-primary/30'
              : 'border-border-input bg-white text-slate-600 hover:border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-500'
          "
        >
          <input v-model="form.role" class="sr-only" type="radio" value="customer" @change="v$.role.$touch()" />
          <span class="block text-sm font-medium">Заказчик</span>
        </label>
        <label
          class="cursor-pointer rounded-full border px-4 py-3 text-center transition"
          :class="
            form.role === 'executor'
              ? 'border-accent-primary bg-accent-primary/10 text-slate-900 dark:text-slate-100 dark:bg-accent-primary/20 dark:border-accent-primary/30'
              : 'border-border-input bg-white text-slate-600 hover:border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-500'
          "
        >
          <input v-model="form.role" class="sr-only" type="radio" value="executor" @change="v$.role.$touch()" />
          <span class="block text-sm font-medium">Исполнитель</span>
        </label>
      </div>
      <p v-if="clientError('role')" class="mt-2 text-sm text-rose-600 dark:text-rose-300">
        {{ clientError('role') }}
      </p>
    </fieldset>

    <!-- Согласие -->
    <label class="mt-4 flex cursor-pointer items-start gap-3">
      <input
        v-model="form.personal_data_consent"
        type="checkbox"
        class="mt-0.5 size-4 rounded border-slate-300 text-accent-primary focus:ring-accent-primary/30 dark:border-slate-600 dark:bg-slate-800"
        @change="v$.personal_data_consent.$touch()"
      />
      <span class="text-sm text-slate-600 dark:text-slate-400">
        Даю согласие на обработку персональных данных
      </span>
    </label>
    <p
      v-if="clientError('personal_data_consent')"
      class="mt-2 text-sm text-rose-600 dark:text-rose-300"
    >
      {{ clientError('personal_data_consent') }}
    </p>

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
      {{ loading ? 'Регистрируем…' : 'Зарегистрироваться' }}
    </button>

    <p class="mt-5 text-center text-sm text-slate-500 dark:text-slate-400">
      Уже есть аккаунт?
      <RouterLink
        class="font-medium text-accent-primary underline-offset-4 hover:underline dark:text-orange-200"
        :to="{ name: 'login' }"
      >
        Войти
      </RouterLink>
    </p>
  </form>
</template>