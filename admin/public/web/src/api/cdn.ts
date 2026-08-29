import { api } from './index'

export const cdnApi = {
  list(params?: Record<string, any>) { return api.get('/admin/cdn/providers', { params }) },
  create(data: any) { return api.post('/admin/cdn/providers', data) },
  update(id: number, data: any) { return api.put(`/admin/cdn/providers/${id}`, data) },
  destroy(id: number) { return api.delete(`/admin/cdn/providers/${id}`) },
  setDefault(id: number) { return api.put(`/admin/cdn/providers/${id}/default`) },
  toggle(id: number) { return api.put(`/admin/cdn/providers/${id}/toggle`) },
  test(id: number) { return api.post(`/admin/cdn/providers/${id}/test`) },
  purge(id: number, paths: string[]) { return api.post(`/admin/cdn/providers/${id}/purge`, { paths }) },
}
