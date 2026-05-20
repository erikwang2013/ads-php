import { api } from './index'

export const campaignApi = {
  list(params?: Record<string, any>) { return api.get('/campaigns', { params }) },
  create(data: Record<string, any>) { return api.post('/campaigns', data) },
  show(id: number) { return api.get(`/campaigns/${id}`) },
  update(id: number, data: Record<string, any>) { return api.put(`/campaigns/${id}`, data) },
  toggle(id: number, enabled: boolean) { return api.post(`/campaigns/${id}/toggle`, { enabled }) },
  batchToggle(ids: number[], enabled: boolean) { return api.post('/campaigns/batch/toggle', { ids, enabled }) },
}
