import { api } from './index'

export const authApi = {
  login(body: { username: string; password: string; captcha_token?: string; captcha_offset?: number }) {
    return api.post('/auth/login', body)
  },
  me() {
    return api.get('/auth/me')
  },
}
