<script setup lang="ts">
import { computed } from 'vue'
import { useTheme } from '@/composables'

const props = withDefaults(
  defineProps<{
    /** Полный логотип с названием или только знак для иконки. */
    variant?: 'full' | 'mark'
    /**
     * `auto` — оригинальный знак на светлом фоне, светлый на тёмном.
     * `on-dark` — всегда светлый вариант.
     */
    tone?: 'default' | 'on-dark' | 'auto'
  }>(),
  { variant: 'full', tone: 'auto' },
)

const { isDark } = useTheme()

const logoSrc = computed(() => {
  if (props.variant === 'mark') {
    return '/logo-mark.png'
  }
  const onDark = props.tone === 'on-dark' || (props.tone === 'auto' && isDark.value)
  return onDark ? '/logo-on-dark.png' : '/logo.png'
})
</script>

<template>
  <img
    :src="logoSrc"
    alt="Делег"
    class="block h-full w-auto max-w-full object-contain object-left"
  />
</template>
