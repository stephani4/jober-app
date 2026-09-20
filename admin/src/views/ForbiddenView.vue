<script setup lang="ts">
import { RouterLink } from 'vue-router'
import Button from 'primevue/button'
import { firstAllowedRoute } from '@/middleware/permission'
</script>

<template>
  <section class="mx-auto max-w-lg space-y-4 py-10 text-center">
    <p class="text-sm uppercase tracking-[0.2em] text-text-secondary">403</p>
    <h1 class="text-2xl font-semibold">Недостаточно прав</h1>
    <p class="text-sm text-text-secondary">
      У вашей роли нет разрешения на эту страницу. Обратитесь к суперадмину, если доступ нужен.
    </p>
    <RouterLink :to="firstAllowedRoute()">
      <Button label="На главную" />
    </RouterLink>
  </section>
</template>
