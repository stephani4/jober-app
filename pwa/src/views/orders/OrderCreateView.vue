<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import OrderCreateDriver from '@/components/orders/OrderCreateDriver.vue'
import { useOrderCreateDriver } from '@/composables'

const router = useRouter()
const { reset } = useOrderCreateDriver()

onMounted(() => {
  reset()
})

function onCreated(): void {
  void router.replace({ name: 'orders' })
}
</script>

<template>
  <OrderCreateDriver @created="onCreated" />
</template>
