import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import Aura from '@primeuix/themes/aura'
import { registerSW } from 'virtual:pwa-register'

import App from './App.vue'
import router from './router'
import { primeVueRuLocale } from '@/locales/primevueRu'
import './style.css'

registerSW({ immediate: true })

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(PrimeVue, {
  locale: primeVueRuLocale,
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.dark',
      cssLayer: {
        name: 'primevue',
        order: 'theme, base, primevue',
      },
    },
  },
})
app.use(ToastService)

app.mount('#app')
