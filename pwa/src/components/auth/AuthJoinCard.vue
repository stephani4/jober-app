<script setup lang="ts">
/**
 * Карточка «Join with …» под формой входа/регистрации (базовый дизайн auth).
 * Аватары-заглушки + текст + кнопка-стрелка.
 */
const avatars = ['А', 'М', 'С', 'Д']
</script>

<template>
  <div
    class="mt-5 w-full max-w-md rounded-[2rem] bg-white py-3 pl-4 pr-3 shadow-card dark:bg-slate-900 dark:ring-1 dark:ring-slate-700"
  >
    <div class="flex items-center gap-3">
      <div class="flex shrink-0 -space-x-2">
        <span
          v-for="(a, i) in avatars"
          :key="i"
          class="flex size-8 shrink-0 items-center justify-center rounded-full border-2 border-white text-xs font-semibold text-white dark:border-slate-900"
          :style="{ backgroundColor: ['#00b5d8', '#5ea8c8', '#294c57', '#9ed2e4'][i % 4] }"
        >
          {{ a }}
        </span>
      </div>
      <p class="min-w-0 flex-1 text-sm leading-snug text-slate-700 dark:text-slate-300">
        Присоединяйтесь к тысячам заказчиков и исполнителей
      </p>
      <button
        type="button"
        aria-label="Присоединиться"
        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-cyan text-white transition hover:bg-accent-cyan-hover"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M5 12h14m-6-6 6 6-6 6" />
        </svg>
      </button>
    </div>
  </div>
</template>