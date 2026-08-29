import { helpers, required } from '@vuelidate/validators'

const { withMessage } = helpers

export const loginValidationRules = {
  login: {
    required: withMessage('Укажите логин', required),
  },
  password: {
    required: withMessage('Укажите пароль', required),
  },
}
