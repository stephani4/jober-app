import { helpers, email, required, requiredIf } from '@vuelidate/validators'

const { withMessage } = helpers

/**
 * Правила формы сотрудника админки. На редактировании пароль необязателен.
 */
export function adminFormValidationRules(isEdit: () => boolean, password: () => string) {
  return {
    name: {
      required: withMessage('Укажите имя', required),
    },
    email: {
      required: withMessage('Укажите email', required),
      email: withMessage('Некорректный email', email),
    },
    password: {
      required: withMessage('Укажите пароль', requiredIf(() => !isEdit())),
      minLength: withMessage(
        'Пароль должен быть не короче 8 символов',
        (value: string) => !value || value.length >= 8,
      ),
    },
    password_confirmation: {
      sameAsPassword: withMessage('Пароли не совпадают', (value: string) => {
        const current = password()
        if (!current) {
          return !value
        }
        return value === current
      }),
    },
    roles: {
      required: withMessage('Выберите хотя бы одну роль', (value: string[]) => Array.isArray(value) && value.length > 0),
    },
  }
}
