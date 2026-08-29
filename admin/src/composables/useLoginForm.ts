import { computed, reactive, ref } from 'vue'
import { isAxiosError } from 'axios'
import useVuelidate from '@vuelidate/core'
import { useAuth } from '@/composables/useAuth'
import { loginPayloadSchema } from '@/schemas/auth'
import { firstVuelidateError, loginValidationRules } from '@/validations'

/**
 * Форма входа в админку.
 */
export function useLoginForm() {
  const auth = useAuth()

  const form = reactive({
    login: '',
    password: '',
  })

  const v$ = useVuelidate(loginValidationRules, form)
  const error = ref('')
  const loading = ref(false)
  const canSubmit = computed(() => !loading.value)

  async function submit(): Promise<boolean> {
    error.value = ''
    const valid = await v$.value.$validate()
    if (!valid) {
      return false
    }

    loading.value = true
    try {
      const payload = loginPayloadSchema.parse({
        login: form.login.trim(),
        password: form.password,
      })
      await auth.login(payload)
      return true
    } catch (err) {
      if (isAxiosError(err)) {
        error.value =
          err.response?.data?.message ||
          err.response?.data?.errors?.login?.[0] ||
          'Не удалось войти. Проверьте данные.'
      } else {
        error.value = 'Не удалось войти. Попробуйте ещё раз.'
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
    firstError: firstVuelidateError,
    submit,
  }
}
