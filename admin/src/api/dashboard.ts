import { api } from './index'

export const dashboardApi = {
  summary(params?: Record<string, any>) {
    return api.get('/reports/summary', { params })
  },
}
