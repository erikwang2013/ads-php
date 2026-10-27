import { api } from './index'

export const campaignApi = {
  list(params?: Record<string, any>) { return api.get('/campaigns', { params }) },
}
