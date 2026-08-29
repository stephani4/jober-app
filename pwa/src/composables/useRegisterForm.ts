import { computed, reactive, ref } from 'vue'
import { isAxiosError } from 'axios'
import useVuelidate from '@vuelidate/core'
import { useAuth } from '@/composables/useAuth'
import { registerPayloadSchema } from '@/schemas/auth'
import type { UserRole } from '@/schemas/user'
import {
  createRegisterValidationRules,
  firstVuelidateError,
} from '@/validations'

export function useRegisterForm() {
  const auth = useAuth()

  const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    birth_date: '',
    role: 'customer' as UserRole,
    personal_data_consent: false,
  })

  const rules = computed(() => createRegisterValidationRules(() => form.password))
  const v$ = useVuelidate(rules, form)

  const error = ref('')
  const fieldErrors = ref<Record<string, string[]>>({})
  const loading = ref(false)
  const canSubmit = computed(() => !loading.value)

  function clientError(field: keyof typeof form): string | undefined {
    const serverError = fieldErrors.value[field]?.[0]
    if (serverError) {
      return serverError
    }
    const state = v$.value[field]
    if (state?.$error) {
      return firstVuelidateError(state.$errors)
    }
    return undefined
  }

  async function submit(): Promise<boolean> {
    error.value = ''
    fieldErrors.value = {}

    const valid = await v$.value.$validate()
    if (!valid) {
      return false
    }

    loading.value = true

    try {
      const payload = registerPayloadSchema.parse({
        name: form.name.trim(),
        email: form.email.trim(),
        password: form.password,
        password_confirmation: form.password_confirmation,
        birth_date: form.birth_date,
        role: form.role,
        personal_data_consent: form.personal_data_consent,
      })

      await auth.register(payload)
      return true
    } catch (err) {
      if (isAxiosError(err)) {
        fieldErrors.value = err.response?.data?.errors || {}
        error.value =
          err.response?.data?.message ||
          'Не удалось зарегистрироваться. Проверьте данные.'
      } else {
        error.value = 'Не удалось зарегистрироваться. Попробуйте ещё раз.'
      }
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    form,
    v$,
    error,
    loading,
    canSubmit,
    clientError,
    submit,
  }
}
