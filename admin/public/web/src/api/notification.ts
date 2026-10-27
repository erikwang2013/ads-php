import { api } from './index'

export const notificationApi = {
  list(params?: Record<string, any>) { return api.get('/notifications', { params }) },
  unreadCount() { return api.get('/notifications/unread-count') },
  markRead(id: number) { return api.post(`/notifications/${id}/read`) },
  markAllRead() { return api.post('/notifications/read-all') },
}
