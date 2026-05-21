<template>
  <div class="page-container">
    <div class="page-header"><h3>归因分析</h3></div>

    <div class="filters" style="margin-bottom:16px">
      <el-date-picker v-model="dateRange" type="daterange" range-separator="至" start-placeholder="起始" end-placeholder="截止" style="width:260px" @change="loadData" />
      <el-select v-model="model" placeholder="归因模型" style="width:200px" @change="loadData">
        <el-option v-for="m in models" :key="m.code" :label="m.name" :value="m.code" />
      </el-select>
    </div>

    <div v-loading="loading">
      <el-row :gutter="16" style="margin-bottom:16px">
        <el-col :span="8"><div class="stat-card"><div class="stat-value">{{ result.total_conversions ?? 0 }}</div><div class="stat-label">转化数</div></div></el-col>
        <el-col :span="8"><div class="stat-card"><div class="stat-value">¥{{ ((result.total_value ?? 0) / 100).toFixed(2) }}</div><div class="stat-label">转化价值</div></div></el-col>
        <el-col :span="8"><div class="stat-card"><div class="stat-value">{{ (result.by_campaign ?? []).length }}</div><div class="stat-label">触点计划</div></div></el-col>
      </el-row>

      <div class="chart-box" v-if="(result.by_campaign ?? []).length > 0">
        <v-chart :option="barOption" autoresize style="height:300px" />
      </div>

      <el-table :data="result.by_campaign ?? []" style="margin-top:16px" v-if="(result.by_campaign ?? []).length > 0">
        <el-table-column prop="campaign_id" label="计划 ID" width="180" />
        <el-table-column label="归因价值" align="right">
          <template #default="{ row }">¥{{ (row.credit / 100).toFixed(2) }}</template>
        </el-table-column>
      </el-table>

      <div v-if="!loading && (result.by_campaign ?? []).length === 0" style="text-align:center;padding:60px;color:#999">
        暂无归因数据，请先导入转化事件 (erik_conversions 表)
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { BarChart } from 'echarts/charts'
import { GridComponent, TooltipComponent } from 'echarts/components'
import { api } from '@/api/index'

use([CanvasRenderer, BarChart, GridComponent, TooltipComponent])

const loading = ref(false)
const models = ref<{ code: string; name: string; description: string }[]>([])
const model = ref('last_touch')
const dateRange = ref<[Date, Date]>([new Date(Date.now() - 30 * 86400000), new Date()])
const result = ref<any>({})

const barOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  xAxis: { type: 'category', data: (result.value.by_campaign ?? []).map((r: any) => '#' + r.campaign_id) },
  yAxis: { type: 'value' },
  series: [{ type: 'bar', data: (result.value.by_campaign ?? []).map((r: any) => r.credit / 100), itemStyle: { color: '#667eea', borderRadius: [4, 4, 0, 0] } }],
}))

async function loadData() {
  loading.value = true
  try {
    const [s, e] = dateRange.value
    result.value = await api.get('/reports/attribution', { params: { model: model.value, date_start: s.toISOString().slice(0, 10), date_end: e.toISOString().slice(0, 10) } })
  } finally { loading.value = false }
}
onMounted(async () => { models.value = await api.get('/reports/attribution/models'); loadData() })
</script>

<style scoped>
.page-container { background: #fff; border-radius: 8px; padding: 16px; }
.page-header { margin-bottom: 16px; }
.page-header h3 { margin: 0; }
.stat-card { background: #f5f7fa; border-radius: 8px; padding: 20px; text-align: center; }
.stat-value { font-size: 28px; font-weight: 700; color: #303133; }
.stat-label { font-size: 13px; color: #909399; margin-top: 4px; }
.chart-box { background: #fafafa; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
</style>
