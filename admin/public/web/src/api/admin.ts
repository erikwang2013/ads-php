import { api } from './index'

export const adminApi = {
  login(username: string, password: string) {
    return api.post('/api/admin/login', { username, password })
  },
  me() {
    return api.get('/api/admin/me')
  },
  logout() {
    return api.post('/api/admin/logout')
  },
  listUsers(params?: any) {
    return api.get('/api/admin/users', { params })
  },
  createUser(data: any) {
    return api.post('/api/admin/users', data)
  },
  updateUser(id: number, data: any) {
    return api.put(`/api/admin/users/${id}`, data)
  },
  deleteUser(id: number) {
    return api.delete(`/api/admin/users/${id}`)
  },
  listRoles() {
    return api.get('/api/admin/roles')
  },
  auditLogs(params?: any) {
    return api.get('/api/admin/audit-logs', { params })
  },
}
