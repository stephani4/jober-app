<script setup lang="ts">
import { computed } from 'vue'
import DatePicker from 'primevue/datepicker'

const model = defineModel<string>({ default: '' })

const props = withDefaults(
  defineProps<{
    /** HTML id для инпута */
    inputId?: string
    /** Плейсхолдер поля */
    placeholder?: string
    /** Максимальная выбираемая дата */
    maxDate?: Date
    /** Минимальная выбираемая дата */
    minDate?: Date
    /** Блокировка ввода */
    disabled?: boolean
    /** Доп. класс корневого элемента */
    class?: string
  }>(),
  {
    placeholder: 'ДД.ММ.ГГГГ',
    disabled: false,
  },
)

const emit = defineEmits<{
  blur: []
}>()

/**
 * Преобразует YYYY-MM-DD в Date без сдвига часового пояса.
 */
function parseModelDate(value: string): Date | null {
  if (!value) {
    return null
  }

  const [year, month, day] = value.split('-').map(Number)
  if (!year || !month || !day) {
    return null
  }

  return new Date(year, month - 1, day)
}

/**
 * Форматирует Date в YYYY-MM-DD для API/форм.
 */
function formatModelDate(value: Date): string {
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const dateValue = computed<Date | null>({
  get: () => parseModelDate(model.value),
  set: (value) => {
    model.value = value instanceof Date ? formatModelDate(value) : ''
  },
})
</script>

<template>
  <!--
    Обёртка над PrimeVue DatePicker:
    русская локаль и неделя с понедельника задаются глобально в main.ts.
  -->
  <DatePicker
    v-model="dateValue"
    :input-id="props.inputId"
    :placeholder="props.placeholder"
    :max-date="props.maxDate"
    :min-date="props.minDate"
    :disabled="props.disabled"
    :class="props.class"
    date-format="dd.mm.yy"
    show-icon
    icon-display="button"
    fluid
    :manual-input="true"
    input-class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-teal-400/60"
    @hide="emit('blur')"
  />
</template>
