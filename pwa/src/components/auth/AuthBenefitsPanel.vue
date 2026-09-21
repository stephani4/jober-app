<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'

/**
 * Правая колонка страниц входа и регистрации (базовый дизайн auth).
 * Голубой градиент + крупный заголовок сверху + стеклянная карточка
 * со слайдером и стрелками prev/next внизу.
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

const current = ref(0)

function next(): void {
  current.value = (current.value + 1) % benefits.length
}

function prev(): void {
  current.value = (current.value - 1 + benefits.length) % benefits.length
}

const AUTOPLAY_MS = 5000
let timer: number | undefined

function startAutoplay(): void {
  stopAutoplay()
  timer = window.setInterval(next, AUTOPLAY_MS)
}

function stopAutoplay(): void {
  if (timer !== undefined) {
    window.clearInterval(timer)
    timer = undefined
  }
}

onMounted(startAutoplay)
onBeforeUnmount(stopAutoplay)
</script>

<template>
  <aside
    class="hidden min-h-dvh flex-col bg-gradient-to-b from-hero-grad-from to-hero-grad-to p-6 text-white lg:flex"
  >
    <div class="flex h-full flex-col rounded-hero bg-white/10 p-8 ring-1 ring-white/20">
      <!-- Крупный заголовок сверху -->
      <h2 class="text-4xl font-semibold leading-tight tracking-tight text-hero-heading">
        Всё для заказа<br />в одном месте
      </h2>

      <!-- Стеклянная карточка со слайдером внизу -->
      <div
        class="mt-auto rounded-3xl bg-white/20 p-6 shadow-lg shadow-black/10 backdrop-blur-md ring-1 ring-white/25"
      >
        <div class="flex items-center justify-between">
          <span class="rounded-pill bg-white/25 px-4 py-1.5 text-xs font-medium text-white">
            Плюсы Делег
          </span>
          <div class="flex gap-2">
            <button
              type="button"
              aria-label="Предыдущий слайд"
              class="flex size-9 items-center justify-center rounded-full bg-white/25 transition hover:bg-white/40"
              @click="prev(); startAutoplay()"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 6l-6 6 6 6" />
              </svg>
            </button>
            <button
              type="button"
              aria-label="Следующий слайд"
              class="flex size-9 items-center justify-center rounded-full bg-white/25 transition hover:bg-white/40"
              @click="next(); startAutoplay()"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 6l6 6-6 6" />
              </svg>
            </button>
          </div>
        </div>

        <div class="mt-6 min-h-40">
          <div :key="current" class="flex items-start gap-4">
            <span aria-hidden="true" class="text-4xl">{{ benefits[current].icon }}</span>
            <div>
              <h3 class="text-xl font-semibold">{{ benefits[current].title }}</h3>
              <p class="mt-2 text-sm leading-relaxed text-white/85">
                {{ benefits[current].text }}
              </p>
            </div>
          </div>
        </div>

        <!-- Индикаторы -->
        <div class="mt-4 flex gap-1.5">
          <button
            v-for="(b, i) in benefits"
            :key="i"
            type="button"
            :aria-label="`Слайд ${i + 1}`"
            class="h-1.5 rounded-pill transition-all"
            :class="i === current ? 'w-6 bg-white' : 'w-1.5 bg-white/40 hover:bg-white/60'"
            @click="current = i; startAutoplay()"
          ></button>
        </div>
      </div>
    </div>
  </aside>
</template>