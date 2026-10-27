import { api } from './index'

export const accountApi = {
  list(params?: Record<string, any>) { return api.get('/accounts', { params }) },
  show(id: number) { return api.get(`/accounts/${id}`) },
  destroy(id: number) { return api.delete(`/accounts/${id}`) },
  sync(id: number) { return api.post(`/accounts/${id}/sync`) },
}
