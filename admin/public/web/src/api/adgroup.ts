import { api } from './index'

export const adGroupApi = {
  list(params?: Record<string, any>) { return api.get('/ad-groups', { params }) },
  create(data: Record<string, any>) { return api.post('/ad-groups', data) },
  show(id: number) { return api.get(`/ad-groups/${id}`) },
  update(id: number, data: Record<string, any>) { return api.put(`/ad-groups/${id}`, data) },
  toggle(id: number, enabled: boolean) { return api.post(`/ad-groups/${id}/toggle`, { enabled }) },
}
