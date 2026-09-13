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
    <RegisterForm @success="onSuccess" />
  </div>
</template>
