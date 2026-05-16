<template>
  <div class="audit-log">
    <div class="page-header">
      <h3>审计日志</h3>
    </div>

    <div class="filters">
      <el-select
        v-model="filters.action"
        placeholder="操作类型"
        clearable
        style="width: 140px"
        @change="fetchList"
      >
        <el-option
          v-for="a in actionOptions"
          :key="a"
          :label="actionLabel(a)"
          :value="a"
        />
      </el-select>
      <el-date-picker
        v-model="filters.dateRange"
        type="daterange"
        range-separator="至"
        start-placeholder="开始日期"
        end-placeholder="结束日期"
        value-format="YYYY-MM-DD"
        style="width: 260px"
        @change="fetchList"
      />
      <el-input
        v-model="filters.user_id"
        placeholder="用户ID"
        clearable
        style="width: 140px"
        @change="fetchList"
      />
    </div>

    <el-table :data="list" v-loading="loading" stripe>
      <el-table-column prop="id" label="ID" width="180" show-overflow-tooltip />
      <el-table-column prop="username" label="操作人" width="120" />
      <el-table-column label="操作类型" width="100">
        <template #default="{ row }">
          <el-tag size="small" :type="actionTagType(row.action)">
            {{ actionLabel(row.action) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="resource" label="资源" width="110" />
      <el-table-column prop="resource_id" label="资源ID" width="200" show-overflow-tooltip />
      <el-table-column prop="ip" label="IP 地址" width="150" />
      <el-table-column prop="created_at" label="操作时间" width="170" />
      <el-table-column label="详情" min-width="200" show-overflow-tooltip>
        <template #default="{ row }">
          <span v-if="row.detail" class="detail-text">{{ JSON.stringify(row.detail) }}</span>
          <span v-else>-</span>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="pagination.page"
      v-model:page-size="pagination.perPage"
      :total="pagination.total"
      layout="total, sizes, prev, pager, next"
      style="margin-top: 16px; justify-content: flex-end"
      @change="fetchList"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { adminApi } from '@/api/admin'

const list = ref<any[]>([])
const loading = ref(false)
const actionOptions = ref<string[]>([])

const filters = reactive({
  action: '',
  user_id: '',
  dateRange: null as [string, string] | null,
})

const pagination = reactive({
  page: 1,
  perPage: 15,
  total: 0,
})

const actionLabels: Record<string, string> = {
  login: '登录',
  logout: '退出',
  create: '创建',
  update: '更新',
  delete: '删除',
}

function actionLabel(action: string): string {
  return actionLabels[action] || action
}

function actionTagType(action: string): string {
  const map: Record<string, string> = {
    login: 'success',
    logout: 'info',
    create: 'primary',
    update: 'warning',
    delete: 'danger',
  }
  return map[action] || 'info'
}

async function fetchList() {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      per_page: pagination.perPage,
    }
    if (filters.action) params.action = filters.action
    if (filters.user_id) params.user_id = filters.user_id
    if (filters.dateRange && filters.dateRange.length === 2) {
      params.date_from = filters.dateRange[0]
      params.date_to = filters.dateRange[1]
    }

    const data: any = await adminApi.auditLogs(params)
    list.value = data.list
    pagination.total = data.total
    if (data.actions) {
      actionOptions.value = data.actions
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style scoped>
.audit-log .page-header {
  margin-bottom: 16px;
}
.audit-log .page-header h3 {
  margin: 0;
}
.audit-log .filters {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.audit-log .detail-text {
  color: #909399;
  font-size: 12px;
}
</style>
