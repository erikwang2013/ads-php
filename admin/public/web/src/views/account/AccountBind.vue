<template>
  <div class="account-bind">
    <h3>绑定平台账户</h3>
    <el-steps :active="step" align-center style="margin: 30px 0;">
      <el-step title="选择平台" />
      <el-step title="授权登录" />
      <el-step title="完成绑定" />
    </el-steps>

    <div v-if="step === 0" class="step-content">
      <div class="platform-grid">
        <div v-for="p in platforms" :key="p.code" class="platform-card" :class="{ selected: selectedPlatform === p.code }" @click="selectedPlatform = p.code">
          <div class="platform-name">{{ p.name }}</div>
          <div class="platform-cap">{{ p.capabilities.join(' / ') }}</div>
        </div>
      </div>
      <el-button type="primary" :disabled="!selectedPlatform" @click="step = 1">下一步</el-button>
    </div>

    <div v-else-if="step === 1" class="step-content">
      <p>点击下方按钮跳转到 {{ platformName }} 授权页面</p>
      <div class="callback-url"><span>回调地址：</span><code>{{ callbackUrl }}</code></div>
      <el-button type="primary" :loading="authLoading" @click="getAuthUrl">前往授权</el-button>
    </div>

    <div v-else-if="step === 2" class="step-content">
      <el-result icon="success" title="绑定成功" sub-title="平台账户已成功绑定">
        <template #extra>
          <el-button type="primary" @click="$router.push('/accounts')">查看账户列表</el-button>
          <el-button @click="reset">继续绑定</el-button>
        </template>
      </el-result>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { platformApi } from '@/api/platform'

const route = useRoute()
const step = ref(0)
const selectedPlatform = ref('')
const platforms = ref<any[]>([])
const authLoading = ref(false)
const callbackUrl = ref(window.location.origin + '/accounts/bind')
const platformName = computed(() => platforms.value.find((x: any) => x.code === selectedPlatform.value)?.name ?? '')

async function getAuthUrl() {
  authLoading.value = true
  const data = await platformApi.getOAuthUrl(selectedPlatform.value, callbackUrl.value)
  sessionStorage.setItem('oauth_state', data.state)
  sessionStorage.setItem('oauth_platform', selectedPlatform.value)
  window.open(data.auth_url, '_blank')
  authLoading.value = false
}

function reset() { step.value = 0; selectedPlatform.value = '' }

onMounted(async () => {
  platforms.value = await platformApi.list()
  const code = route.query.code as string
  const state = route.query.state as string
  if (code && state) {
    const savedState = sessionStorage.getItem('oauth_state')
    const savedPlatform = sessionStorage.getItem('oauth_platform')
    if (state === savedState && savedPlatform) {
      try {
        await platformApi.callback(savedPlatform, state, code)
        step.value = 2
        sessionStorage.removeItem('oauth_state')
        sessionStorage.removeItem('oauth_platform')
      } catch (e: any) { /* bind failed */ }
    }
  }
})
</script>

<style scoped>
.account-bind { max-width: 700px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 24px; }
.step-content { text-align: center; padding: 40px 0; }
.platform-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
.platform-card { padding: 20px; border: 2px solid #e4e7ed; border-radius: 8px; cursor: pointer; transition: border-color 0.3s; }
.platform-card.selected { border-color: #409EFF; }
.platform-name { font-size: 16px; font-weight: 500; margin-bottom: 8px; }
.platform-cap { font-size: 12px; color: #909399; }
.callback-url { margin: 12px 0; }
.callback-url code { background: #f5f5f5; padding: 2px 6px; border-radius: 4px; }
</style>
