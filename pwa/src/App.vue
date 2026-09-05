<script setup lang="ts">
import { RouterView } from 'vue-router'
import Toast from 'primevue/toast'
import { usePwaInstall, useRealtime, useTheme, useWebPush } from '@/composables'

useTheme()
useRealtime()
usePwaInstall()
useWebPush()
</script>

<template>
  <Toast position="top-center" />
  <RouterView />
</template>
