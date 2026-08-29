<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import LoginForm from '@/components/auth/LoginForm.vue'
import { useAuth, useRealtime } from '@/composables'
import { restoreActiveOrder } from '@/composables/useRestoreActiveOrder'

const router = useRouter()
const route = useRoute()
const { user } = useAuth()
const { status } = useRealtime()

const info =
  route.query.registered === '1' ? 'Регистрация успешна. Войдите в аккаунт.' : ''
const redirectTo =
  typeof route.query.redirect === 'string' ? route.query.redirect : '/orders'

async function waitRealtime(timeoutMs = 8000): Promise<void> {
  const startedAt = Date.now()
  while (status.value !== 'connected' && Date.now() - startedAt < timeoutMs) {
    await new Promise((resolve) => window.setTimeout(resolve, 50))
  }
}

async function onSuccess(path: string): Promise<void> {
  await waitRealtime()
  const restored = await restoreActiveOrder(router, user.value?.id ?? null)
  if (!restored) {
    await router.replace(path)
  }
}
</script>

<template>
  <div class="w-full max-w-md">
    <div class="mb-8 text-center">
      <p class="text-sm uppercase tracking-[0.25em] text-teal-700 dark:text-teal-300/80">Jober</p>
      <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
        Вход
      </h1>
      <p class="mt-2 text-slate-500 dark:text-slate-400">
        Войдите, чтобы смотреть и брать заказы
      </p>
    </div>

    <LoginForm :info="info" :redirect-to="redirectTo" @success="onSuccess" />
  </div>
</template>
