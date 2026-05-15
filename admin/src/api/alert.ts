import { api } from './index'

export const alertApi = {
  listRules(params?: any) {
    return api.get('/alerts/rules', { params })
  },
  createRule(data: any) {
    return api.post('/alerts/rules', data)
  },
  updateRule(id: number, data: any) {
    return api.put(`/alerts/rules/${id}`, data)
  },
  deleteRule(id: number) {
    return api.delete(`/alerts/rules/${id}`)
  },
  listLogs(params?: any) {
    return api.get('/alerts/logs', { params })
  },
  acknowledge(id: number) {
    return api.post(`/alerts/logs/${id}/acknowledge`)
  },
  unreadCount() {
    return api.get('/alerts/unread-count')
  },
}
