import axios from 'axios'
import { api } from './index'

const raw = axios.create({
  baseURL: '/api',
  timeout: 30000,
  responseType: 'blob',
})

raw.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  config.headers['X-API-Version'] = 'v1'
  config.headers['X-Client-Platform'] = 'web'
  return config
})

export const exportApi = {
  exportReport(params: Record<string, any>) {
    return raw.get('/reports/export', { params })
  },
  exportDashboard(params: Record<string, any>) {
    return raw.get('/reports/export-dashboard', { params })
  },
}

export const reportApi = {
  custom(params?: Record<string, any>) { return api.get('/reports/custom', { params }) },
}
