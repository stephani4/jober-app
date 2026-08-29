<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useOrderChatDriver } from '@/composables/useOrderChat'
import type { OrderMessage } from '@/schemas/orderMessage'

/** Панель чата заказа: история, ввод и realtime-сообщения. */

const props = defineProps<{
  orderId: number | null
  readonly?: boolean
}>()

const orderId = computed(() => props.orderId)
const { user } = useAuth()
const { open, messages, draft, loading, sending, error, send } = useOrderChatDriver(
  orderId,
  Boolean(props.readonly),
)
const scroller = ref<HTMLElement | null>(null)
const canSend = computed(() => draft.value.trim().length > 0 && !sending.value)

function isMine(message: OrderMessage): boolean {
  return user.value?.id === message.user_id
}

function timeLabel(message: OrderMessage): string {
  if (!message.created_at) {
    return ''
  }
  const date = new Date(message.created_at)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return new Intl.DateTimeFormat('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

async function onSubmit(): Promise<void> {
  if (!canSend.value) {
    return
  }
  await send()
}

async function scrollToBottom(): Promise<void> {
  await nextTick()
  const el = scroller.value
  if (el) {
    el.scrollTop = el.scrollHeight
  }
}

watch(
  [open, messages],
  ([isOpen]) => {
    if (isOpen || props.readonly) {
      void scrollToBottom()
    }
  },
)
</script>

<template>
  <div
    v-if="readonly || open"
    class="flex flex-col bg-surface-page dark:bg-zinc-950"
    :class="readonly ? 'min-h-0 flex-1' : 'absolute inset-0 z-20'"
    role="dialog"
    aria-modal="true"
    aria-label="Чат заказа"
  >
    <div ref="scroller" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4">
      <p v-if="loading && messages.length === 0" class="text-sm text-text-secondary">
        Загружаем чат…
      </p>
      <p v-else-if="!loading && messages.length === 0" class="text-sm text-text-secondary">
        {{ readonly ? 'Сообщений нет' : 'Напишите первое сообщение' }}
      </p>

      <article
        v-for="message in messages"
        :key="message.id"
        class="flex"
        :class="isMine(message) ? 'justify-end' : 'justify-start'"
      >
        <div
          class="max-w-[80%] rounded-2xl px-3 py-2"
          :class="
            isMine(message)
              ? 'rounded-br-md bg-accent-nav text-white'
              : 'rounded-bl-md bg-surface-muted text-text-primary dark:bg-zinc-800 dark:text-zinc-100'
          "
        >
          <p
            v-if="!isMine(message)"
            class="text-xs text-text-secondary dark:text-zinc-400"
          >
            {{ message.user?.name ?? 'Участник' }}
          </p>
          <p class="whitespace-pre-wrap break-words text-sm">{{ message.body }}</p>
          <p
            v-if="timeLabel(message)"
            class="mt-1 text-right text-[10px] opacity-70"
          >
            {{ timeLabel(message) }}
          </p>
        </div>
      </article>
    </div>

    <form
      v-if="!readonly"
      class="border-t border-border-subtle bg-surface-card px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 dark:border-white/10 dark:bg-zinc-900"
      @submit.prevent="onSubmit"
    >
      <p v-if="error" class="mb-2 text-sm text-accent-danger">{{ error }}</p>
      <div class="flex items-end gap-2">
        <label class="sr-only" for="order-chat-input">Сообщение</label>
        <textarea
          id="order-chat-input"
          v-model="draft"
          rows="1"
          maxlength="2000"
          :disabled="sending"
          placeholder="Сообщение"
          class="max-h-28 min-h-11 flex-1 resize-none rounded-2xl border border-border-subtle bg-surface-muted px-3 py-2.5 text-sm text-text-primary outline-none dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-100"
          @keydown.enter.exact.prevent="onSubmit"
        />
        <button
          type="submit"
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-accent-nav text-white disabled:opacity-40"
          :disabled="!canSend"
          aria-label="Отправить"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M22 2 11 13" />
            <path d="M22 2 15 22l-4-9-9-4Z" />
          </svg>
        </button>
      </div>
    </form>
  </div>
</template>
