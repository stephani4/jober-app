<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  close: []
  confirm: [reason: string]
}>()

const reason = ref('')
const error = ref('')

watch(
  () => props.open,
  (open) => {
    if (open) {
      reason.value = ''
      error.value = ''
    }
  },
)

function submit(): void {
  const value = reason.value.trim()
  if (value.length < 3) {
    error.value = 'Укажите причину отказа.'
    return
  }
  emit('confirm', value)
}
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    @click.self="emit('close')"
  >
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-lg dark:bg-zinc-900">
      <h2 class="text-lg font-semibold">Отклонить заказ</h2>
      <p class="mt-1 text-sm text-text-secondary">Причина увидит автор в истории заказов.</p>
      <Textarea
        v-model="reason"
        :rows="4"
        fluid
        class="mt-4"
        placeholder="Например: в заказе присутствует нецензурная лексика"
      />
      <p v-if="error" class="mt-2 text-sm text-accent-danger">{{ error }}</p>
      <div class="mt-4 flex justify-end gap-2">
        <Button
          label="Отмена"
          severity="secondary"
          variant="outlined"
          @click="emit('close')"
        />
        <Button
          label="Отклонить"
          severity="danger"
          @click="submit"
        />
      </div>
    </div>
  </div>
</template>
