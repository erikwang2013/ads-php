import { api } from './index'

// 注意：axios 实例 baseURL 为 '/api'（见 ./index.ts），
// 此处一律使用相对路径（'/admin/...'），最终请求为 '/api/admin/...'，避免双前缀 404。
export const adminApi = {
  login(username: string, password: string) {
    return api.post('/admin/login', { username, password })
  },
  me() {
    return api.get('/admin/me')
  },
  logout() {
    return api.post('/admin/logout')
  },
}
