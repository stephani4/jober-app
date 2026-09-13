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
    <LoginForm :info="info" :redirect-to="redirectTo" @success="onSuccess" />
  </div>
</template>
