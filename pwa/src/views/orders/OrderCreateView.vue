<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import OrderCreateDriver from '@/components/orders/OrderCreateDriver.vue'
import { useOrderCreateDriver, useOrderTypes } from '@/composables'

const router = useRouter()
const { orderType, reset } = useOrderCreateDriver()
const { openPicker } = useOrderTypes()

onMounted(() => {
  if (!orderType.value) {
    openPicker()
  }
})

onUnmounted(() => {
  reset()
})

function onCreated(): void {
  void router.replace({ name: 'orders' })
}
</script>

<template>
  <div v-if="!orderType" class="space-y-4 pt-8 text-center">
    <p class="text-sm text-text-secondary">Сначала выберите вид заказа</p>
    <button
      type="button"
      class="rounded-xl bg-accent-nav px-4 py-3 text-white"
      @click="openPicker()"
    >
      Выбрать вид
    </button>
  </div>
  <OrderCreateDriver v-else @created="onCreated" />
</template>
