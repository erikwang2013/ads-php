import { defineStore } from 'pinia'
import { ref } from 'vue'
import { notificationApi } from '@/api/notification'

export const useNotificationStore = defineStore('notification', () => {
  const unreadCount = ref(0)
  let timer: ReturnType<typeof setInterval> | null = null

  async function fetchUnread() {
    try {
      const data: any = await notificationApi.unreadCount()
      unreadCount.value = data.count ?? 0
    } catch { /* ignore polling errors */ }
  }

  function startPolling() {
    fetchUnread()
    timer = setInterval(fetchUnread, 30000)
  }

  function stopPolling() {
    if (timer) { clearInterval(timer); timer = null }
  }

  return { unreadCount, fetchUnread, startPolling, stopPolling }
})
