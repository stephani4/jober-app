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
    class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/30"
    @submit.prevent="onSubmit"
  >
    <div class="space-y-2">
      <label class="block text-sm text-slate-600 dark:text-slate-300" for="name">ФИО</label>
      <input
        id="name"
        v-model="form.name"
        type="text"
        autocomplete="name"
        placeholder="Иванов Иван Иванович"
        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-teal-400/60"
        @blur="v$.name.$touch()"
      />
      <p v-if="clientError('name')" class="text-sm text-rose-600 dark:text-rose-300">
        {{ clientError('name') }}
      </p>
    </div>

    <div class="space-y-2">
      <label class="block text-sm text-slate-600 dark:text-slate-300" for="email">Email</label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        autocomplete="email"
        placeholder="email@example.com"
        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-teal-400/60"
        @blur="v$.email.$touch()"
      />
      <p v-if="clientError('email')" class="text-sm text-rose-600 dark:text-rose-300">
        {{ clientError('email') }}
      </p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
      <div class="space-y-2">
        <label class="block text-sm text-slate-600 dark:text-slate-300" for="password">Пароль</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-teal-400/60"
          @blur="v$.password.$touch()"
        />
        <p v-if="clientError('password')" class="text-sm text-rose-600 dark:text-rose-300">
          {{ clientError('password') }}
        </p>
      </div>
      <div class="space-y-2">
        <label class="block text-sm text-slate-600 dark:text-slate-300" for="password_confirmation">
          Повтор пароля
        </label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-teal-400/60"
          @blur="v$.password_confirmation.$touch()"
        />
        <p v-if="clientError('password_confirmation')" class="text-sm text-rose-600 dark:text-rose-300">
          {{ clientError('password_confirmation') }}
        </p>
      </div>
    </div>

    <div class="space-y-2">
      <label class="block text-sm text-slate-600 dark:text-slate-300" for="birth_date">
        Дата рождения
      </label>
      <CustomDatepicker
        v-model="form.birth_date"
        input-id="birth_date"
        :max-date="new Date()"
        class="w-full"
        @blur="v$.birth_date.$touch()"
      />
      <p v-if="clientError('birth_date')" class="text-sm text-rose-600 dark:text-rose-300">
        {{ clientError('birth_date') }}
      </p>
    </div>

    <fieldset class="space-y-3">
      <legend class="text-sm text-slate-600 dark:text-slate-300">Роль</legend>
      <div class="grid gap-3 sm:grid-cols-2">
        <label
          class="cursor-pointer rounded-xl border px-4 py-3 transition"
          :class="
            form.role === 'customer'
              ? 'border-teal-500 bg-teal-50 dark:border-teal-400/50 dark:bg-teal-400/10'
              : 'border-slate-200 bg-slate-50 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-slate-600'
          "
        >
          <input v-model="form.role" class="sr-only" type="radio" value="customer" />
          <span class="block font-medium text-slate-900 dark:text-slate-100">Заказчик</span>
          <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Публикуете задания</span>
        </label>
        <label
          class="cursor-pointer rounded-xl border px-4 py-3 transition"
          :class="
            form.role === 'executor'
              ? 'border-teal-500 bg-teal-50 dark:border-teal-400/50 dark:bg-teal-400/10'
              : 'border-slate-200 bg-slate-50 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-slate-600'
          "
        >
          <input v-model="form.role" class="sr-only" type="radio" value="executor" />
          <span class="block font-medium text-slate-900 dark:text-slate-100">Исполнитель</span>
          <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Выполняете задания</span>
        </label>
      </div>
      <p class="text-xs text-slate-500 dark:text-slate-400">
        Роль можно будет сменить после регистрации.
      </p>
      <p v-if="clientError('role')" class="text-sm text-rose-600 dark:text-rose-300">
        {{ clientError('role') }}
      </p>
    </fieldset>

    <label
      class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"
    >
      <input
        v-model="form.personal_data_consent"
        type="checkbox"
        class="mt-1 size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-teal-400"
        @change="v$.personal_data_consent.$touch()"
      />
      <span class="text-sm text-slate-700 dark:text-slate-300">
        Даю согласие на обработку персональных данных
      </span>
    </label>
    <p v-if="clientError('personal_data_consent')" class="text-sm text-rose-600 dark:text-rose-300">
      {{ clientError('personal_data_consent') }}
    </p>

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
      {{ loading ? 'Регистрируем…' : 'Зарегистрироваться' }}
    </button>

    <p class="text-center text-sm text-slate-500 dark:text-slate-400">
      Уже есть аккаунт?
      <RouterLink
        class="text-teal-700 underline-offset-4 hover:underline dark:text-teal-300"
        :to="{ name: 'login' }"
      >
        Войти
      </RouterLink>
    </p>
  </form>
</template>
