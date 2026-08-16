<template>
  <div class="sync-status-page">
    <div class="page-header">
      <h3>同步状态</h3>
      <el-button :loading="loading || errorsLoading" @click="fetchAll">
        <el-icon style="margin-right: 4px;"><Refresh /></el-icon>
        刷新
      </el-button>
    </div>

    <!-- 摘要卡片：总账户 / 24h 已同步 / 近7天错误 / 待重试 -->
    <div class="metric-row">
      <MetricCard label="总账户" :value="summary?.total_accounts ?? 0" format="number" />
      <MetricCard label="24h 已同步" :value="summary?.synced_24h ?? 0" format="number" />
      <MetricCard label="近7天错误" :value="summary?.error_7d ?? 0" format="number" />
      <MetricCard label="待重试" :value="summary?.pending_retries ?? 0" format="number" />
    </div>

    <!-- 账户同步状态 -->
    <div class="panel">
      <h4>账户同步状态</h4>
      <el-table :data="accounts" v-loading="loading" stripe empty-text="暂无账户同步数据">
        <el-table-column label="账户名称" prop="account_name" min-width="180" show-overflow-tooltip />
        <el-table-column label="平台" width="140">
          <template #default="{ row }"><PlatformBadge :platform="row.platform" /></template>
        </el-table-column>
        <el-table-column label="最后同步时间" width="180">
          <template #default="{ row }">{{ row.last_sync_at || '未同步' }}</template>
        </el-table-column>
        <el-table-column label="近7天错误数" width="110" align="center">
          <template #default="{ row }">
            <el-tag v-if="(row.sync_errors_count ?? 0) > 0" type="danger" size="small">{{ row.sync_errors_count }}</el-tag>
            <span v-else>0</span>
          </template>
        </el-table-column>
        <el-table-column label="待重试" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="(row.pending_retries ?? 0) > 0" type="warning" size="small">{{ row.pending_retries }}</el-tag>
            <span v-else>0</span>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 同步错误明细 -->
    <div class="panel">
      <h4>同步错误明细</h4>
      <el-table :data="errors" v-loading="errorsLoading" stripe empty-text="暂无同步错误">
        <el-table-column label="账户" prop="account_name" min-width="160" show-overflow-tooltip />
        <el-table-column label="平台" width="140">
          <template #default="{ row }"><PlatformBadge :platform="row.platform" /></template>
        </el-table-column>
        <el-table-column label="重试次数" width="100" align="center">
          <template #default="{ row }">{{ row.retry_count }}</template>
        </el-table-column>
        <el-table-column label="错误信息" prop="last_error" min-width="240" show-overflow-tooltip />
        <el-table-column label="下次重试" width="180">
          <template #default="{ row }">{{ row.next_retry_at || '—' }}</template>
        </el-table-column>
        <el-table-column label="创建时间" prop="created_at" width="180" />
      </el-table>
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.perPage"
        :total="pagination.total"
        layout="total, sizes, prev, pager, next"
        class="list-pagination"
        @change="fetchErrors"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Refresh } from '@element-plus/icons-vue'
import MetricCard from '@/components/MetricCard.vue'
import PlatformBadge from '@/components/PlatformBadge.vue'
import { syncApi } from '@/api/sync'
import type { SyncStatusSummary, SyncAccountStatus, SyncErrorItem } from '@/api/sync'

const loading = ref(false)
const errorsLoading = ref(false)
const summary = ref<SyncStatusSummary | null>(null)
const accounts = ref<SyncAccountStatus[]>([])
const errors = ref<SyncErrorItem[]>([])

// 同步错误明细分页
const pagination = reactive({ page: 1, perPage: 20, total: 0 })

async function fetchStatus() {
  loading.value = true
  try {
    const data = await syncApi.status()
    summary.value = data.summary ?? null
    accounts.value = data.accounts ?? []
  } finally {
    loading.value = false
  }
}

async function fetchErrors() {
  errorsLoading.value = true
  try {
    const data = await syncApi.errors({ page: pagination.page, per_page: pagination.perPage })
    errors.value = data.list ?? []
    pagination.total = data.pagination?.total ?? 0
  } finally {
    errorsLoading.value = false
  }
}

async function fetchAll() {
  await Promise.all([fetchStatus(), fetchErrors()])
}

onMounted(fetchAll)
</script>

<style scoped>
.sync-status-page {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.page-header h3 {
  margin: 0;
}

.metric-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}
@media (max-width: 1200px) {
  .metric-row { grid-template-columns: repeat(2, 1fr); }
}

.panel {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #ebeef5;
}
.panel + .panel {
  margin-top: 16px;
}
.panel h4 {
  margin: 0 0 12px;
  font-size: 16px;
  color: #303133;
}
.list-pagination {
  margin-top: 16px;
  justify-content: flex-end;
}
</style>
