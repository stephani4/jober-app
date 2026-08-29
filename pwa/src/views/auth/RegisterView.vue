<script setup lang="ts">
import { useRouter } from 'vue-router'
import RegisterForm from '@/components/auth/RegisterForm.vue'

const router = useRouter()

async function onSuccess(): Promise<void> {
  await router.replace({
    name: 'login',
    query: { registered: '1' },
  })
}
</script>

<template>
  <div class="w-full max-w-xl">
    <div class="mb-8 text-center">
      <p class="text-sm uppercase tracking-[0.25em] text-teal-700 dark:text-teal-300/80">Jober</p>
      <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
        Регистрация
      </h1>
      <p class="mt-2 text-slate-500 dark:text-slate-400">
        Создайте аккаунт заказчика или исполнителя
      </p>
    </div>

    <RegisterForm @success="onSuccess" />
  </div>
</template>
