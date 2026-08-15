/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useAdminStore = defineStore('admin', () => {
  const systemInfo = ref<any>(null)

  // service 的 GET /health 返回 { status, timestamp, checks: { database, redis } }
  async function fetchSystemInfo() {
    try { systemInfo.value = (await axios.get('/health', { timeout: 5000 })).data } catch {}
  }

  return { systemInfo, fetchSystemInfo }
})
