/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Global confirmation dialog store — "type to confirm" pattern
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface ConfirmOptions {
  title?: string
  message: string
  confirmWord?: string
  confirmText?: string
  requireTyping?: boolean
  onConfirm: () => void | Promise<void>
  onCancel?: () => void
}

export const useConfirmStore = defineStore('confirm', () => {
  const visible = ref(false)
  const loading = ref(false)
  const typedWord = ref('')
  const options = ref<ConfirmOptions | null>(null)

  function show(opts: ConfirmOptions) {
    options.value = { confirmWord: 'DELETE', confirmText: '确认删除', requireTyping: true, ...opts }
    typedWord.value = ''
    loading.value = false
    visible.value = true
  }

  async function confirm() {
    if (!options.value) return
    loading.value = true
    try {
      await options.value.onConfirm()
      visible.value = false
    } finally {
      loading.value = false
    }
  }

  function cancel() {
    options.value?.onCancel?.()
    visible.value = false
  }

  return { visible, loading, typedWord, options, show, confirm, cancel }
})
