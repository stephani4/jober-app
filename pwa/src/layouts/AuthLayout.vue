<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import Layout from '@/layouts/Layout.vue'
import AppBottomNav from '@/components/AppBottomNav.vue'
import AppHeroHeader from '@/components/AppHeroHeader.vue'
import OrderOfferModal from '@/components/orders/OrderOfferModal.vue'
import OrderTypePickerModal from '@/components/orders/OrderTypePickerModal.vue'
import PwaPromptBanner from '@/components/PwaPromptBanner.vue'

const route = useRoute()
const hideNav = computed(() => Boolean(route.meta.hideNav))
const fullBleed = computed(() => Boolean(route.meta.fullBleed))
</script>

<template>
  <Layout :viewport="fullBleed">
    <template #hero>
      <AppHeroHeader />
    </template>

    <main
      class="flex min-h-0 flex-1 flex-col"
      :class="fullBleed ? '' : hideNav ? 'px-4 pt-4 pb-8' : 'px-4 pt-4 pb-24'"
    >
      <PwaPromptBanner v-if="!hideNav && !fullBleed" class="mb-3" />
      <RouterView />
    </main>

    <template v-if="!hideNav" #footer>
      <AppBottomNav />
    </template>
  </Layout>
  <OrderOfferModal />
  <OrderTypePickerModal />
</template>
