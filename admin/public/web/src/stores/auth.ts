import { defineStore } from 'pinia'
import { ref } from 'vue'
import { authApi } from '@/api/auth'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('access_token') || '')
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  async function login(username: string, password: string, captcha?: {token: string, offset: number}) {
    const body: any = { username, password }
    if (captcha) {
      body.captcha_token = captcha.token
      body.captcha_offset = captcha.offset
    }
    const data = await authApi.login(body)
    token.value = data.access_token
    user.value = data.user
    localStorage.setItem('access_token', data.access_token)
    localStorage.setItem('user', JSON.stringify(data.user))
    router.push('/dashboard')
  }

  function logout() {
    token.value = ''
    user.value = null
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')
    router.push('/login')
  }

  return { token, user, login, logout }
})
