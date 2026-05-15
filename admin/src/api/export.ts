import axios from 'axios'

const raw = axios.create({
  baseURL: '/api/v1',
  timeout: 30000,
  responseType: 'blob',
})

raw.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export const exportApi = {
  /**
   * Download report export file.
   * @param params - query params: format, date_start, date_end, dimensions, metrics, platform
   */
  exportReport(params: Record<string, any>) {
    return raw.get('/reports/export', { params })
  },
}
