import { defaultOptions } from 'primevue/config'

/**
 * Русская локаль PrimeVue: неделя начинается с понедельника.
 */
export const primeVueRuLocale = {
  ...defaultOptions.locale,
  firstDayOfWeek: 1,
  dayNames: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
  dayNamesShort: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
  dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
  monthNames: [
    'Январь',
    'Февраль',
    'Март',
    'Апрель',
    'Май',
    'Июнь',
    'Июль',
    'Август',
    'Сентябрь',
    'Октябрь',
    'Ноябрь',
    'Декабрь',
  ],
  monthNamesShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
  today: 'Сегодня',
  clear: 'Очистить',
  weekHeader: 'Нед',
  dateFormat: 'dd.mm.yy',
  chooseDate: 'Выбрать дату',
  chooseMonth: 'Выбрать месяц',
  chooseYear: 'Выбрать год',
  prevMonth: 'Предыдущий месяц',
  nextMonth: 'Следующий месяц',
  prevYear: 'Предыдущий год',
  nextYear: 'Следующий год',
  emptyFilterMessage: 'Результатов не найдено',
  emptyMessage: 'Нет доступных вариантов',
  emptySearchMessage: 'Результатов не найдено',
  emptySelectionMessage: 'Ничего не выбрано',
  searchMessage: 'Найдено результатов: {0}',
  selectionMessage: 'Выбрано элементов: {0}',
}
