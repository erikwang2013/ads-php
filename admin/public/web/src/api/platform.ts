import { api } from './index'

export const platformApi = {
  list() { return api.get('/platforms') },
  getOAuthUrl(platform: string, redirectUri: string) {
    return api.get(`/platforms/${platform}/oauth-url`, { params: { redirect_uri: redirectUri } })
  },
  callback(platform: string, state: string, code: string) {
    return api.post(`/platforms/${platform}/callback`, { state, code })
  },
}
