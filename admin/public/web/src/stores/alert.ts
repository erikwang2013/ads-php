import { defineStore } from 'pinia'
import { ref } from 'vue'
import { alertApi } from '@/api/alert'

export const useAlertStore = defineStore('alert', () => {
  const unreadCount = ref(0)
  let timer: ReturnType<typeof setInterval> | null = null

  async function fetchUnread() {
    try {
      const data: any = await alertApi.unreadCount()
      unreadCount.value = data.count ?? 0
    } catch {
      // silently ignore polling errors
    }
  }

  function startPolling() {
    fetchUnread()
    timer = setInterval(fetchUnread, 30000)
  }

  function stopPolling() {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  return { unreadCount, fetchUnread, startPolling, stopPolling }
})
