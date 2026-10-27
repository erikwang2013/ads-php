<template>
  <div class="system-info-page">
    <h2>系统信息</h2>

    <el-row :gutter="20">
      <el-col :span="8">
        <el-card shadow="hover">
          <template #header>服务状态</template>
          <div v-if="serviceOk === null">检测中...</div>
          <el-tag v-else-if="serviceOk" type="success">正常</el-tag>
          <el-tag v-else type="danger">不可达</el-tag>
        </el-card>
      </el-col>

      <el-col :span="8">
        <el-card shadow="hover">
          <template #header>管理后台版本</template>
          <p>webman-admin v2</p>
        </el-card>
      </el-col>

      <el-col :span="8">
        <el-card shadow="hover">
          <template #header>PHP 版本</template>
          <p>PHP {{ phpVersion }}</p>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" style="margin-top: 20px">
      <el-col :span="8">
        <el-card shadow="hover">
          <template #header>数据库状态</template>
          <div v-if="dbOk === null">检测中...</div>
          <el-tag v-else-if="dbOk" type="success">连接正常</el-tag>
          <el-tag v-else type="danger">连接失败</el-tag>
        </el-card>
      </el-col>
    </el-row>

    <!-- 套餐配额用量（GET /api/tenant/quota） -->
    <el-row :gutter="20" style="margin-top: 20px">
      <el-col :span="24">
        <el-card shadow="hover">
          <template #header>
            <div class="quota-header">
              <span>套餐配额</span>
              <el-tag v-if="quota" size="small" :type="planTagType">{{ planLabel }}</el-tag>
            </div>
          </template>
          <div v-if="quotaLoading" class="quota-tip">加载中...</div>
          <div v-else-if="quotaError" class="quota-tip">配额信息加载失败</div>
          <div v-else-if="quotaItems.length" class="quota-body">
            <div v-for="item in quotaItems" :key="item.key" class="quota-item">
              <div class="quota-label">
                <span>{{ item.label }}</span>
                <span class="quota-num" :class="{ danger: item.limit > 0 && item.usage >= item.limit }">
                  {{ item.usage }} / {{ item.limit > 0 ? item.limit : '不限' }}
                </span>
              </div>
              <el-progress
                :percentage="item.percentage"
                :stroke-width="10"
                :color="item.percentage >= 100 ? '#F56C6C' : item.percentage >= 80 ? '#E6A23C' : '#409EFF'"
              />
            </div>
          </div>
          <div v-else class="quota-tip">暂无配额信息</div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { platformApi } from '@/api/platform'
import { tenantApi } from '@/api/tenant'
import type { TenantQuota } from '@/api/tenant'

const serviceOk = ref<boolean | null>(null)
const dbOk = ref<boolean | null>(null)
const phpVersion = ref('8.2+')

const quota = ref<TenantQuota | null>(null)
const quotaLoading = ref(false)
const quotaError = ref(false)

// 套餐 plan 代码 → 展示名 / 徽标颜色（后端契约：lite/standard/full）
const planLabelMap: Record<string, string> = { lite: '精简版', standard: '标准版', full: '专业版' }
const planTagTypeMap: Record<string, 'info' | 'primary' | 'success'> = { lite: 'info', standard: 'primary', full: 'success' }
const planLabel = computed(() => (quota.value ? (planLabelMap[quota.value.plan] ?? quota.value.plan) : ''))
const planTagType = computed<'info' | 'primary' | 'success'>(() => (quota.value ? (planTagTypeMap[quota.value.plan] ?? 'info') : 'info'))

// 三项用量/限额 → 进度条（limit <= 0 视为不限，进度按 0 处理，展示 "不限"）
const quotaItems = computed(() => {
  const q = quota.value
  if (!q) return []
  const defs = [
    { key: 'accounts', label: '账户数量', usage: q.usage.accounts, limit: q.limits.account_limit },
    { key: 'campaigns', label: '广告计划数', usage: q.usage.campaigns, limit: q.limits.campaign_limit },
    { key: 'sync_daily', label: '每日同步次数', usage: q.usage.sync_today, limit: q.limits.sync_daily },
  ]
  return defs.map((d) => ({
    ...d,
    percentage: d.limit > 0 ? Math.min(100, Math.round((d.usage / d.limit) * 100)) : 0,
  }))
})

async function fetchQuota() {
  quotaLoading.value = true
  quotaError.value = false
  try {
    quota.value = await tenantApi.getQuota()
  } catch {
    // 429/403 等超限错误由 api 拦截器统一弹出，此处仅标记加载失败态
    quotaError.value = true
    quota.value = null
  } finally {
    quotaLoading.value = false
  }
}

onMounted(async () => {
  try {
    await platformApi.list()
    serviceOk.value = true
  } catch {
    serviceOk.value = false
  }

  // 数据库连通性探测：service 的 GET /health 返回 { status, timestamp, checks: { database, redis } }，
  // 不是统一 envelope（{code,message,data}），且其路由不带 /api 前缀。
  // 因此用原生 axios 直连（绕过 index.ts 响应拦截器的 envelope 解包与 baseURL），
  // 由 vite 代理将 /health 转发到 :8788（见 vite.config.ts）。
  try {
    const res = await axios.get('/health', { timeout: 5000 })
    dbOk.value = res.data?.checks?.database === 'ok'
  } catch {
    dbOk.value = false
  }

  fetchQuota()
})
</script>

<style scoped>
.system-info-page h2 {
  margin-bottom: 20px;
}
.quota-header {
  display: flex;
  align-items: center;
  gap: 8px;
}
.quota-body {
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.quota-tip {
  color: #909399;
  font-size: 14px;
}
.quota-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.quota-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
  color: #303133;
}
.quota-num {
  font-weight: 600;
}
.quota-num.danger {
  color: #f56c6c;
}
</style>
