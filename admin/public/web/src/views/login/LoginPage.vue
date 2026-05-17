<template>
  <div class="login-container">
    <div class="login-card">
      <h2>广告管理系统</h2>
      <el-form ref="formRef" :model="form" :rules="rules" size="large">
        <el-form-item prop="username">
          <el-input v-model="form.username" placeholder="用户名">
            <template #prefix>
              <el-icon><User /></el-icon>
            </template>
          </el-input>
        </el-form-item>
        <el-form-item prop="password">
          <el-input v-model="form.password" type="password" placeholder="密码" show-password @keyup.enter="handleLogin">
            <template #prefix>
              <el-icon><Lock /></el-icon>
            </template>
          </el-input>
        </el-form-item>
        <!-- Captcha -->
        <el-form-item v-if="showCaptcha">
          <CaptchaWidget @verified="onCaptchaVerified" @close="showCaptcha = false" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" style="width:100%" @click="handleLogin">登 录</el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import type { FormInstance } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import CaptchaWidget from '@/components/CaptchaWidget.vue'

const authStore = useAuthStore()
const formRef = ref<FormInstance>()
const loading = ref(false)
const form = reactive({ username: 'admin', password: 'admin123' })
const rules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
}
const showCaptcha = ref(false)
const captchaData = ref<{token:string,offset:number}|null>(null)

async function handleLogin() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  if (!captchaData.value) {
    showCaptcha.value = true
    return
  }
  loading.value = true
  try {
    await authStore.login(form.username, form.password, captchaData.value)
    captchaData.value = null
  } finally { loading.value = false }
}

function onCaptchaVerified(data: {token: string, offset: number}) {
  captchaData.value = data
  showCaptcha.value = false
  handleLogin()
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f2f5;
}
.login-card { width: 400px; padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
.login-card h2 { text-align: center; margin-bottom: 30px; color: #303133; }
</style>
