<script setup lang="ts">
import { useRouter } from 'vue-router'
import LoginForm from '@/components/LoginForm.vue'

const router = useRouter()

async function onSuccess(): Promise<void> {
  await router.replace({ name: 'orders' })
}
</script>

<template>
  <div class="w-full max-w-md">
    <div class="mb-8 text-center">
      <p class="text-sm uppercase tracking-[0.25em] text-zinc-500">Jober</p>
      <h1 class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">Вход в админку</h1>
      <p class="mt-2 text-sm text-zinc-500">Только для сотрудников. Отдельный аккаунт, не PWA.</p>
    </div>
    <LoginForm @success="onSuccess" />
  </div>
</template>
