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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { platformApi } from '@/api/platform'

const serviceOk = ref<boolean | null>(null)
const dbOk = ref<boolean | null>(null)
const phpVersion = ref('8.2+')

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
})
</script>

<style scoped>
.system-info-page h2 {
  margin-bottom: 20px;
}
</style>
