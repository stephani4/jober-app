import { computed } from 'vue'
import { helpers, minLength, required, sameAs } from '@vuelidate/validators'

const { withMessage } = helpers

export const loginValidationRules = {
  login: {
    required: withMessage('Укажите логин', required),
  },
  password: {
    required: withMessage('Укажите пароль', required),
  },
}

export function createRegisterValidationRules(getPassword: () => string) {
  return {
    name: {
      required: withMessage('Укажите ФИО', required),
      minLength: withMessage('ФИО слишком короткое', minLength(2)),
    },
    email: {
      required: withMessage('Укажите email', required),
      email: withMessage(
        'Некорректный email',
        helpers.regex(/^[^\s@]+@[^\s@]+\.[^\s@]+$/),
      ),
    },
    password: {
      required: withMessage('Укажите пароль', required),
      minLength: withMessage('Пароль не короче 8 символов', minLength(8)),
    },
    password_confirmation: {
      required: withMessage('Повторите пароль', required),
      sameAsPassword: withMessage(
        'Пароли не совпадают',
        sameAs(computed(getPassword)),
      ),
    },
    birth_date: {
      required: withMessage('Укажите дату рождения', required),
      beforeToday: withMessage(
        'Дата рождения должна быть раньше сегодняшнего дня',
        helpers.withParams({ type: 'beforeToday' }, (value: string) => {
          if (!value) {
            return true
          }
          return new Date(value) < new Date(new Date().toDateString())
        }),
      ),
    },
    role: {
      required: withMessage('Выберите роль', required),
      oneOf: withMessage(
        'Выберите роль',
        helpers.withParams(
          { type: 'oneOf' },
          (value: string) => value === 'customer' || value === 'executor',
        ),
      ),
    },
    personal_data_consent: {
      accepted: withMessage(
        'Необходимо согласие на обработку персональных данных',
        (value: boolean) => value === true,
      ),
    },
  }
}
