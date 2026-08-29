import type { ErrorObject } from '@vuelidate/core'

export function firstVuelidateError(
  fieldErrors: ErrorObject[] | undefined,
): string {
  return fieldErrors?.[0]?.$message?.toString() ?? ''
}
