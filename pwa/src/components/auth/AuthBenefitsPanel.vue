<script setup lang="ts">
import Carousel from 'primevue/carousel'

/**
 * Правая колонка страниц входа и регистрации.
 * Слайдер на PrimeVue Carousel с плюсами приложения (временная реализация).
 * Позже карточки можно заменить на реальные иллюстрации/картинки.
 */

interface Benefit {
  icon: string
  title: string
  text: string
}

const benefits: Benefit[] = [
  {
    icon: '📍',
    title: 'Заказы рядом с вами',
    text: 'Находите заказчиков и исполнителей поблизости — без долгого поиска.',
  },
  {
    icon: '⚡',
    title: 'Отклик в один клик',
    text: 'Откликайтесь на задания и получайте заказы быстрее других.',
  },
  {
    icon: '💬',
    title: 'Встроенный чат',
    text: 'Обсуждайте детали заказа с заказчиком прямо в приложении.',
  },
  {
    icon: '🔔',
    title: 'Пуш-уведомления',
    text: 'Не пропускайте новые заказы — приложение пришлёт уведомление.',
  },
  {
    icon: '🛡️',
    title: 'Безопасность данных',
    text: 'Персональные данные надёжно защищены, согласие — под контролем.',
  },
]
</script>

<template>
  <aside
    class="hidden min-h-dvh flex-col items-center justify-center gap-10 bg-surface-hero px-6 py-12 text-text-on-hero lg:flex"
  >
    <p class="text-sm uppercase tracking-[0.25em] text-text-on-hero-muted">Jober</p>

    <!-- Обёртка гарантирует центрирование карусели по правой части экрана -->
    <div class="flex w-full justify-center">
      <Carousel
        :value="benefits"
        num-visible="1"
        num-scroll="1"
        circular
        :autoplay-interval="5000"
        class="w-full max-w-md"
      >
        <template #item="slotProps">
          <div
            class="flex h-72 w-full flex-col items-center justify-center rounded-3xl border border-white/15 bg-gradient-to-br from-white/15 to-white/5 px-6 text-center shadow-lg shadow-black/30"
          >
            <span aria-hidden="true" class="text-5xl">{{ slotProps.data.icon }}</span>
            <h2 class="mt-5 text-xl font-semibold text-text-on-hero">{{ slotProps.data.title }}</h2>
            <p class="mt-3 text-sm leading-relaxed text-text-on-hero-muted">
              {{ slotProps.data.text }}
            </p>
          </div>
        </template>
      </Carousel>
    </div>
  </aside>
</template>

<style>
/*
 * Обходной фикс для PrimeVue Carousel 4.5.5.
 *
 * После смены слайда компонент навешивает на контейнер класс p-items-hidden,
 * который прячет ВСЕ слайды (visibility: hidden), кроме отмеченного
 * p-carousel-item-active. В циклическом режиме с автопрокруткой отметка
 * активного слайда расходится с реально видимым — слайд «пропадает».
 * Переопределяем правило, чтобы контент слайдов никогда не скрывался.
 */
.p-carousel-item-list.p-items-hidden .p-carousel-item {
  visibility: visible;
}
</style>