import { api } from './index'

export const authApi = {
  login(username: string, password: string) {
    return api.post('/auth/login', { username, password })
  },
  me() {
    return api.get('/auth/me')
  },
}
