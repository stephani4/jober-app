<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { firstAllowedRoute } from '@/middleware/permission'

const router = useRouter()

onMounted(() => {
  void router.replace(firstAllowedRoute())
})
</script>

<template>
  <p class="text-sm text-text-secondary">Перенаправление…</p>
</template>
