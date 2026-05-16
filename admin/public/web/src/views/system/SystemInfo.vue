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

  try {
    const { api } = await import('@/api/index')
    await api.get('/system/info')
    dbOk.value = true
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
