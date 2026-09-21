<script setup lang="ts">
import { RouterView } from 'vue-router'
import Toast from 'primevue/toast'
import { useOrderSound, usePwaInstall, useRealtime, useTheme, useWebPush } from '@/composables'

useTheme()
useRealtime()
usePwaInstall()
useWebPush()
// Разблокировка звука на первом жесте: событие заказа может прийти до открытия профиля.
useOrderSound()
</script>

<template>
  <Toast position="top-center" />
  <RouterView />
</template>
