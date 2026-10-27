import axios from 'axios'
import type { AxiosInstance, AxiosResponse, InternalAxiosRequestConfig } from 'axios'
import { ElMessage } from 'element-plus'

// Backend unified response wrapper
interface ApiResponse<T = any> {
  code: number
  message: string
  data: T
}

// Custom Axios instance that unwraps the ApiResponse envelope
interface UnwrappedInstance extends Omit<AxiosInstance, 'request' | 'get' | 'delete' | 'head' | 'options' | 'post' | 'put' | 'patch'> {
  request<T = any>(config: InternalAxiosRequestConfig): Promise<T>
  get<T = any>(url: string, config?: any): Promise<T>
  delete<T = any>(url: string, config?: any): Promise<T>
  head<T = any>(url: string, config?: any): Promise<T>
  options<T = any>(url: string, config?: any): Promise<T>
  post<T = any>(url: string, data?: any, config?: any): Promise<T>
  put<T = any>(url: string, data?: any, config?: any): Promise<T>
  patch<T = any>(url: string, data?: any, config?: any): Promise<T>
}

// API 版本号固定在 URL 路径上（service 业务 API：/api/v1/...）。
// '/admin/...' 走 admin 后端（:8789，不版本化），仅 '/admin/cdn/...'
// 是 service 上的 admin 分组，同样需要 /v1 前缀。
export function versionedPath(url: string): string {
  if (url.startsWith('/admin') && !url.startsWith('/admin/cdn')) return url
  return url.startsWith('/v1') ? url : '/v1' + url
}

const raw = axios.create({
  baseURL: '/api',
  timeout: 15000,
})

raw.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  config.url = versionedPath(config.url ?? '')
  config.headers['X-Client-Platform'] = 'web'
  const csrf = localStorage.getItem('csrf_token')
  if (csrf && ['post', 'put', 'delete', 'patch'].includes(config.method?.toLowerCase() || '')) {
    config.headers['X-CSRF-Token'] = csrf
  }
  return config
})

raw.interceptors.response.use(
  (response: AxiosResponse<ApiResponse>) => {
    const { code, message, data } = response.data
    if (code !== 0) {
      ElMessage.error(message || '请求失败')
      return Promise.reject(new Error(message))
    }
    return data
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('access_token')
      window.location.href = '/login'
    }
    ElMessage.error(error.message || '网络错误')
    return Promise.reject(error)
  }
)

const api = raw as unknown as UnwrappedInstance

export default api
export { api }
export type { ApiResponse }
