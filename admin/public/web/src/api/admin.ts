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
  listUsers(params?: any) {
    return api.get('/admin/users', { params })
  },
  createUser(data: any) {
    return api.post('/admin/users', data)
  },
  updateUser(id: number, data: any) {
    return api.put(`/admin/users/${id}`, data)
  },
  deleteUser(id: number) {
    return api.delete(`/admin/users/${id}`)
  },
  // 用户编辑页下拉框使用的角色列表 → 对应 route.php 中受保护的 GET /api/admin/users/roles
  listRoles() {
    return api.get('/admin/users/roles')
  },
  auditLogs(params?: any) {
    return api.get('/admin/audit-logs', { params })
  },
}
