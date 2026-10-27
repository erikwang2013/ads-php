<template>
  <div class="alert-log-list">
    <div class="page-header">
      <h3>告警记录</h3>
      <el-button type="default" @click="$router.push('/alerts')">返回规则列表</el-button>
    </div>

    <div class="filters">
      <el-select v-model="filter.status" placeholder="状态" clearable style="width:140px" @change="fetchList">
        <el-option label="已触发" value="triggered" />
        <el-option label="已确认" value="acknowledged" />
        <el-option label="已解决" value="resolved" />
      </el-select>
      <el-select v-model="filter.metric" placeholder="指标" clearable style="width:140px" @change="fetchList">
        <el-option v-for="m in metricOptions" :key="m.value" :label="m.label" :value="m.value" />
      </el-select>
      <el-select v-model="filter.rule_id" placeholder="规则" clearable style="width:200px" @change="fetchList">
        <el-option v-for="r in rules" :key="r.id" :label="r.name" :value="r.id" />
      </el-select>
    </div>

    <el-table :data="list" v-loading="loading" stripe>
      <el-table-column prop="rule_name" label="规则名称" min-width="160" show-overflow-tooltip />
      <el-table-column label="指标" width="120">
        <template #default="{ row }">
          <el-tag size="small">{{ metricLabel(row.metric) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="当前值" width="130" align="right">
        <template #default="{ row }">
          <span v-if="isRatioMetric(row.metric)">{{ formatPercent(row.current_value) }}</span>
          <span v-else>{{ formatNumber(row.current_value) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="阈值" width="100" align="right">
        <template #default="{ row }">
          <span v-if="isRatioMetric(row.metric)">{{ formatPercent(row.threshold) }}</span>
          <span v-else>{{ formatNumber(row.threshold) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="条件" width="80" align="center">
        <template #default="{ row }">{{ conditionLabel(row.condition) }}</template>
      </el-table-column>
      <el-table-column label="触发时间" width="170" align="center">
        <template #default="{ row }">{{ row.created_at }}</template>
      </el-table-column>
      <el-table-column label="状态" width="100" align="center">
        <template #default="{ row }">
          <el-tag :type="statusTagType(row.status)" size="small">{{ statusLabel(row.status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="120" align="center">
        <template #default="{ row }">
          <el-button
            v-if="row.status === 'triggered'"
            size="small"
            type="warning"
            @click="handleAcknowledge(row.id)"
          >
            确认
          </el-button>
          <span v-else style="color:#909399">--</span>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="pagination.page"
      v-model:page-size="pagination.perPage"
      :total="pagination.total"
      layout="total, sizes, prev, pager, next"
      style="margin-top:16px; justify-content:flex-end"
      @change="fetchList"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { alertApi } from '@/api/alert'

const loading = ref(false)
const list = ref<any[]>([])
const rules = ref<any[]>([])

const filter = reactive({ status: '', metric: '', rule_id: null as number | null })
const pagination = reactive({ page: 1, perPage: 20, total: 0 })

const metricOptions = [
  { value: 'cost', label: '花费' },
  { value: 'impressions', label: '展示量' },
  { value: 'clicks', label: '点击量' },
  { value: 'conversions', label: '转化数' },
  { value: 'ctr', label: '点击率(CTR)' },
  { value: 'cvr', label: '转化率(CVR)' },
  { value: 'roi', label: 'ROI' },
]

const ratioMetrics = ['ctr', 'cvr', 'roi']

function metricLabel(val: string) {
  return metricOptions.find(m => m.value === val)?.label ?? val
}

function conditionLabel(val: string) {
  const map: Record<string, string> = { gt: '>', gte: '>=', lt: '<', lte: '<=' }
  return map[val] ?? val
}

function statusLabel(val: string) {
  const map: Record<string, string> = { triggered: '已触发', acknowledged: '已确认', resolved: '已解决' }
  return map[val] ?? val
}

function statusTagType(val: string) {
  const map: Record<string, string> = { triggered: 'danger', acknowledged: 'warning', resolved: 'success' }
  return map[val] ?? 'info'
}

function isRatioMetric(metric: string) {
  return ratioMetrics.includes(metric)
}

function formatNumber(val: number) {
  if (!val && val !== 0) return '-'
  if (val >= 100000000) return (val / 100000000).toFixed(2) + '亿'
  if (val >= 10000) return (val / 10000).toFixed(2) + '万'
  return Number(val).toLocaleString()
}

function formatPercent(val: number) {
  return (val * 100).toFixed(2) + '%'
}

async function fetchList() {
  loading.value = true
  try {
    const params: any = { ...pagination }
    if (filter.status) params.status = filter.status
    if (filter.metric) params.metric = filter.metric
    if (filter.rule_id) params.rule_id = filter.rule_id
    const data: any = await alertApi.listLogs(params)
    list.value = data.list ?? []
    pagination.total = data.pagination?.total ?? 0
  } finally {
    loading.value = false
  }
}

async function handleAcknowledge(id: number) {
  try {
    await alertApi.acknowledge(id)
    ElMessage.success('已确认')
    fetchList()
  } catch {
    // error handled by interceptor
  }
}

async function loadRules() {
  try {
    const data: any = await alertApi.listRules({ per_page: 200 })
    rules.value = data.list ?? []
  } catch {
    // ignore
  }
}

onMounted(() => {
  loadRules()
  fetchList()
})
</script>

<style scoped>
.alert-log-list {
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
.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}
</style>
