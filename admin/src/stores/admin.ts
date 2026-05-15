/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/api/index'

export const useAdminStore = defineStore('admin', () => {
  const systemInfo = ref<any>(null)

  async function fetchSystemInfo() {
    try { systemInfo.value = await api.get('/system/info') } catch {}
  }

  return { systemInfo, fetchSystemInfo }
})
