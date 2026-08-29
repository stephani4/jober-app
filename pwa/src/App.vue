<script setup lang="ts">
import { RouterView } from 'vue-router'
import Toast from 'primevue/toast'
import { useRealtime, useTheme } from '@/composables'

useTheme()
useRealtime()
</script>

<template>
  <Toast position="top-center" />
  <RouterView />
</template>
