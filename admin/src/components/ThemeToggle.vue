<script setup lang="ts">
import Button from 'primevue/button'
import { useTheme } from '@/composables'

const { isDark, toggle } = useTheme()
</script>

<template>
  <Button
    type="button"
    size="small"
    severity="secondary"
    variant="outlined"
    :label="isDark ? 'Светлая' : 'Тёмная'"
    :aria-label="isDark ? 'Включить светлую тему' : 'Включить тёмную тему'"
    @click="toggle()"
  />
</template>
