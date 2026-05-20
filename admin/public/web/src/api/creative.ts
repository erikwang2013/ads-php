import { api } from './index'

export const creativeApi = {
  list(params?: Record<string, any>) { return api.get('/creatives', { params }) },
  show(id: number) { return api.get(`/creatives/${id}`) },
}
