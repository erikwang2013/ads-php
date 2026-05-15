<template>
  <div class="dashboard">
    <!-- Header: date range + export buttons -->
    <div class="dashboard-header">
      <div class="header-left">
        <h2>数据概览</h2>
      </div>
      <div class="header-right">
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          value-format="YYYY-MM-DD"
          :shortcuts="dateShortcuts"
          style="width: 260px; margin-right: 12px;"
          @change="reload"
        />
        <el-button @click="handleExportPdf" :loading="exportingPdf">
          <el-icon style="margin-right: 4px;"><Printer /></el-icon>
          导出PDF
        </el-button>
        <el-button @click="handleExportExcel" :loading="exportingExcel">
          <el-icon style="margin-right: 4px;"><Download /></el-icon>
          导出Excel
        </el-button>
      </div>
    </div>

    <!-- KPI metric cards -->
    <div class="metric-row">
      <MetricCard label="总花费" :value="overview?.total_cost ?? 0" format="money" :trend="costTrend" />
      <MetricCard label="展示量" :value="overview?.total_impressions ?? 0" format="number" :trend="impressionsTrend" />
      <MetricCard label="点击量" :value="overview?.total_clicks ?? 0" format="number" :trend="clicksTrend" />
      <MetricCard label="转化量" :value="overview?.total_conversions ?? 0" format="number" :trend="conversionsTrend" />
      <MetricCard label="点击率" :value="(overview?.avg_ctr ?? 0) / 100" format="percent" :trend="ctrTrend" />
      <MetricCard label="转化率" :value="(overview?.avg_cvr ?? 0) / 100" format="percent" :trend="cvrTrend" />
      <MetricCard label="平均CPC" :value="overview?.avg_cpc ?? 0" format="money" :trend="cpcTrend" />
      <MetricCard label="平均CPA" :value="overview?.avg_cpa ?? 0" format="money" :trend="cpaTrend" />
    </div>

    <!-- KPI day-over-day comparison row -->
    <div class="kpi-compare-row" v-if="hasComparison">
      <div class="kpi-compare-item" v-for="item in kpiComparison" :key="item.label">
        <span class="kpi-label">{{ item.label }}</span>
        <span class="kpi-today">{{ item.today }}</span>
        <span class="kpi-yesterday">{{ item.yesterday }}</span>
        <span class="kpi-change" :class="item.direction">
          {{ item.direction === 'up' ? '↑' : item.direction === 'down' ? '↓' : '—' }}
          {{ item.changePct }}
        </span>
      </div>
    </div>

    <!-- Daily trend chart -->
    <div class="chart-section">
      <h4>每日花费趋势</h4>
      <v-chart :option="trendOption" style="height:400px" autoresize />
    </div>

    <!-- Platform comparison: bar chart + table -->
    <el-row :gutter="16">
      <el-col :span="12">
        <div class="panel">
          <h4>平台花费对比</h4>
          <v-chart :option="barOption" style="height:320px" autoresize />
        </div>
      </el-col>
      <el-col :span="12">
        <div class="panel">
          <h4>TOP10 广告计划</h4>
          <el-table :data="topCampaigns" size="small" max-height="320">
            <el-table-column prop="name" label="计划名称" show-overflow-tooltip />
            <el-table-column label="平台" width="80">
              <template #default="{ row }"><PlatformBadge :platform="row.platform" /></template>
            </el-table-column>
            <el-table-column label="花费" width="100" align="right">
              <template #default="{ row }">{{ formatFen(row.total_cost) }}</template>
            </el-table-column>
          </el-table>
        </div>
      </el-col>
    </el-row>

    <!-- Data summary table -->
    <div class="panel" style="margin-top: 16px;">
      <h4>平台数据明细</h4>
      <el-table :data="platformSummary" size="small" stripe>
        <el-table-column prop="platform" label="平台" width="100">
          <template #default="{ row }"><PlatformBadge :platform="row.platform" /></template>
        </el-table-column>
        <el-table-column label="花费" align="right">
          <template #default="{ row }">{{ formatFen(row.cost) }}</template>
        </el-table-column>
        <el-table-column label="展示量" align="right">
          <template #default="{ row }">{{ row.impressions?.toLocaleString() }}</template>
        </el-table-column>
        <el-table-column label="点击量" align="right">
          <template #default="{ row }">{{ row.clicks?.toLocaleString() }}</template>
        </el-table-column>
        <el-table-column label="点击率(CTR)" align="right">
          <template #default="{ row }">{{ (row.ctr ?? 0).toFixed(2) }}%</template>
        </el-table-column>
        <el-table-column label="转化率(CVR)" align="right">
          <template #default="{ row }">{{ (row.cvr ?? 0).toFixed(2) }}%</template>
        </el-table-column>
        <el-table-column label="点击均价(CPC)" align="right">
          <template #default="{ row }">{{ formatFen(row.cpc ?? 0) }}</template>
        </el-table-column>
        <el-table-column label="转化量" align="right">
          <template #default="{ row }">{{ row.conversions?.toLocaleString() }}</template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Printer, Download } from '@element-plus/icons-vue'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, PieChart, BarChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import MetricCard from '@/components/MetricCard.vue'
import PlatformBadge from '@/components/PlatformBadge.vue'
import { formatFen } from '@/utils/format'
import { dashboardApi } from '@/api/dashboard'
import { exportApi } from '@/api/export'
import { campaignApi } from '@/api/campaign'

use([CanvasRenderer, LineChart, PieChart, BarChart, GridComponent, TooltipComponent, LegendComponent])

const overview = ref<any>(null)
const byPlatform = ref<any[]>([])
const daily = ref<any[]>([])
const topCampaigns = ref<any[]>([])
const exportingPdf = ref(false)
const exportingExcel = ref(false)

// Date range — default last 7 days
const dateRange = ref<[string, string]>([
  new Date(Date.now() - 7 * 86400000).toISOString().slice(0, 10),
  new Date().toISOString().slice(0, 10),
])

const dateShortcuts = [
  { text: '今天', value: () => { const d = new Date(); const s = d.toISOString().slice(0, 10); return [s, s] as [string, string] } },
  { text: '昨天', value: () => { const d = new Date(Date.now() - 86400000); const s = d.toISOString().slice(0, 10); return [s, s] as [string, string] } },
  { text: '最近7天', value: () => { const end = new Date(); const start = new Date(Date.now() - 7 * 86400000); return [start.toISOString().slice(0, 10), end.toISOString().slice(0, 10)] as [string, string] } },
  { text: '最近30天', value: () => { const end = new Date(); const start = new Date(Date.now() - 30 * 86400000); return [start.toISOString().slice(0, 10), end.toISOString().slice(0, 10)] as [string, string] } },
]

// --- Trend line chart ---
const trendOption = computed(() => {
  const platforms = [...new Set(daily.value.map((d: any) => d.platform))] as string[]
  const dates = [...new Set(daily.value.map((d: any) => d.date))].sort() as string[]
  return {
    tooltip: { trigger: 'axis' },
    legend: { data: platforms },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { type: 'category', data: dates },
    yAxis: { type: 'value', name: '花费 (元)' },
    series: platforms.map((p: string) => ({
      name: p, type: 'line', smooth: true,
      data: dates.map((date: string) => {
        const d = daily.value.find((x: any) => x.date === date && x.platform === p)
        return d ? d.cost / 100 : 0
      }),
    })),
  }
})

// --- Horizontal bar chart for platform cost comparison ---
const barOption = computed(() => {
  const platforms = byPlatform.value.map((p: any) => p.platform)
  const costs = byPlatform.value.map((p: any) => p.cost / 100)
  return {
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    grid: { left: '3%', right: '10%', bottom: '3%', containLabel: true },
    xAxis: { type: 'value', name: '花费 (元)' },
    yAxis: { type: 'category', data: platforms },
    series: [{
      type: 'bar',
      data: costs,
      itemStyle: { color: '#409EFF', borderRadius: [0, 4, 4, 0] },
      label: { show: true, position: 'right', formatter: (p: any) => '¥' + p.value.toFixed(2) },
    }],
  }
})

// --- Platform summary table data (with computed CTR/CVR/CPC) ---
const platformSummary = computed(() => {
  return byPlatform.value.map((p: any) => ({
    platform: p.platform,
    cost: p.cost ?? 0,
    impressions: p.impressions ?? 0,
    clicks: p.clicks ?? 0,
    conversions: p.conversions ?? 0,
    ctr: p.impressions > 0 ? (p.clicks / p.impressions * 100) : 0,
    cvr: p.clicks > 0 ? (p.conversions / p.clicks * 100) : 0,
    cpc: p.clicks > 0 ? (p.cost / p.clicks) : 0,
  }))
})

// --- KPI day-over-day comparison ---
const hasComparison = computed(() => daily.value.length > 0)

const dailyAggregated = computed(() => {
  const map: Record<string, any> = {}
  for (const d of daily.value) {
    if (!map[d.date]) {
      map[d.date] = { cost: 0, impressions: 0, clicks: 0, conversions: 0 }
    }
    map[d.date].cost += d.cost || 0
    map[d.date].impressions += d.impressions || 0
    map[d.date].clicks += d.clicks || 0
    map[d.date].conversions += d.conversions || 0
  }
  const sortedDates = Object.keys(map).sort()
  return { map, sortedDates }
})

const calcPctChange = (field: string): number | undefined => {
  const { map, sortedDates } = dailyAggregated.value
  if (sortedDates.length < 2) return undefined
  const last = map[sortedDates[sortedDates.length - 1]]?.[field] ?? 0
  const prev = map[sortedDates[sortedDates.length - 2]]?.[field] ?? 0
  if (prev === 0) return last > 0 ? 100 : 0
  return ((last - prev) / prev) * 100
}

const costTrend = computed(() => calcPctChange('cost'))
const impressionsTrend = computed(() => calcPctChange('impressions'))
const clicksTrend = computed(() => calcPctChange('clicks'))
const conversionsTrend = computed(() => calcPctChange('conversions'))
const ctrTrend = computed(() => {
  const { map, sortedDates } = dailyAggregated.value
  if (sortedDates.length < 2) return undefined
  const last = map[sortedDates[sortedDates.length - 1]]
  const prev = map[sortedDates[sortedDates.length - 2]]
  const lastCtr = last.impressions > 0 ? last.clicks / last.impressions : 0
  const prevCtr = prev.impressions > 0 ? prev.clicks / prev.impressions : 0
  if (prevCtr === 0) return lastCtr > 0 ? 100 : 0
  return ((lastCtr - prevCtr) / prevCtr) * 100
})
const cvrTrend = computed(() => {
  const { map, sortedDates } = dailyAggregated.value
  if (sortedDates.length < 2) return undefined
  const last = map[sortedDates[sortedDates.length - 1]]
  const prev = map[sortedDates[sortedDates.length - 2]]
  const lastCvr = last.clicks > 0 ? last.conversions / last.clicks : 0
  const prevCvr = prev.clicks > 0 ? prev.conversions / prev.clicks : 0
  if (prevCvr === 0) return lastCvr > 0 ? 100 : 0
  return ((lastCvr - prevCvr) / prevCvr) * 100
})
const cpcTrend = computed(() => {
  const { map, sortedDates } = dailyAggregated.value
  if (sortedDates.length < 2) return undefined
  const last = map[sortedDates[sortedDates.length - 1]]
  const prev = map[sortedDates[sortedDates.length - 2]]
  const lastCpc = last.clicks > 0 ? last.cost / last.clicks : 0
  const prevCpc = prev.clicks > 0 ? prev.cost / prev.clicks : 0
  if (prevCpc === 0) return lastCpc > 0 ? 100 : 0
  return ((lastCpc - prevCpc) / prevCpc) * 100
})
const cpaTrend = computed(() => {
  const { map, sortedDates } = dailyAggregated.value
  if (sortedDates.length < 2) return undefined
  const last = map[sortedDates[sortedDates.length - 1]]
  const prev = map[sortedDates[sortedDates.length - 2]]
  // CPA = cost per conversion (stored in fen, but we just need ratio)
  const lastCpa = last.conversions > 0 ? last.cost / last.conversions : 0
  const prevCpa = prev.conversions > 0 ? prev.cost / prev.conversions : 0
  if (prevCpa === 0) return lastCpa > 0 ? 100 : 0
  return ((lastCpa - prevCpa) / prevCpa) * 100
})

const kpiComparison = computed(() => {
  const { map, sortedDates } = dailyAggregated.value
  if (sortedDates.length < 2) return []

  const last = map[sortedDates[sortedDates.length - 1]]
  const prev = map[sortedDates[sortedDates.length - 2]]

  const items = [
    { label: '花费', today: '¥' + (last.cost / 100).toFixed(2), yesterday: '¥' + (prev.cost / 100).toFixed(2), field: 'cost' as const },
    { label: '展示量', today: last.impressions.toLocaleString(), yesterday: prev.impressions.toLocaleString(), field: 'impressions' as const },
    { label: '点击量', today: last.clicks.toLocaleString(), yesterday: prev.clicks.toLocaleString(), field: 'clicks' as const },
    { label: '转化量', today: last.conversions.toLocaleString(), yesterday: prev.conversions.toLocaleString(), field: 'conversions' as const },
  ]

  return items.map(item => {
    const cur = last[item.field] ?? 0
    const pre = prev[item.field] ?? 0
    let changePct = '0%'
    let direction = 'flat'
    if (pre === 0) {
      changePct = cur > 0 ? '+100%' : '0%'
      direction = cur > 0 ? 'up' : 'flat'
    } else {
      const pct = ((cur - pre) / pre) * 100
      changePct = (pct >= 0 ? '+' : '') + pct.toFixed(1) + '%'
      direction = pct > 0 ? 'up' : pct < 0 ? 'down' : 'flat'
    }
    return { ...item, changePct, direction }
  })
})

// --- Export handlers ---
const buildParams = () => {
  const params: Record<string, any> = { format: 'pdf' }
  if (dateRange.value && dateRange.value.length === 2) {
    params.date_start = dateRange.value[0]
    params.date_end = dateRange.value[1]
  }
  return params
}

const downloadBlob = (blob: Blob, filename: string) => {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const handleExportPdf = async () => {
  exportingPdf.value = true
  try {
    const params = buildParams()
    const response = await exportApi.exportDashboard(params)
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    downloadBlob(blob, 'dashboard_' + formatDate(new Date()) + '.pdf')
    ElMessage.success('PDF导出成功')
  } catch (err: any) {
    ElMessage.error('PDF导出失败: ' + (err.message || '未知错误'))
  } finally {
    exportingPdf.value = false
  }
}

const handleExportExcel = async () => {
  exportingExcel.value = true
  try {
    const params = buildParams()
    params.format = 'excel'
    const response = await exportApi.exportReport(params)
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    downloadBlob(blob, 'dashboard_' + formatDate(new Date()) + '.xls')
    ElMessage.success('Excel导出成功')
  } catch (err: any) {
    ElMessage.error('Excel导出失败: ' + (err.message || '未知错误'))
  } finally {
    exportingExcel.value = false
  }
}

const formatDate = (d: Date) => {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const h = String(d.getHours()).padStart(2, '0')
  const min = String(d.getMinutes()).padStart(2, '0')
  const s = String(d.getSeconds()).padStart(2, '0')
  return `${y}${m}${day}${h}${min}${s}`
}

// --- Data loading ---
const loadData = async () => {
  let params: Record<string, any> | undefined
  if (dateRange.value && dateRange.value.length === 2) {
    params = {
      date_start: dateRange.value[0],
      date_end: dateRange.value[1],
    }
  }

  const data = await dashboardApi.summary(params)
  overview.value = data.overview
  byPlatform.value = data.by_platform
  daily.value = data.daily

  const campaigns = await campaignApi.list({ per_page: 10, sort: 'cost' })
  topCampaigns.value = campaigns.list
}

const reload = () => {
  loadData()
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}
.header-left h2 {
  margin: 0;
  font-size: 20px;
  color: #303133;
}
.header-right {
  display: flex;
  align-items: center;
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

.kpi-compare-row {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.kpi-compare-item {
  flex: 1 1 200px;
  background: #fff;
  border-radius: 8px;
  padding: 12px 16px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.kpi-label {
  font-size: 12px;
  color: #909399;
}
.kpi-today {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}
.kpi-yesterday {
  font-size: 12px;
  color: #909399;
}
.kpi-change {
  font-size: 13px;
  font-weight: 600;
}
.kpi-change.up { color: #67C23A; }
.kpi-change.down { color: #F56C6C; }
.kpi-change.flat { color: #909399; }

.chart-section {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 16px;
}
.chart-section h4 {
  margin: 0 0 16px;
  font-size: 16px;
  color: #303133;
}

.panel {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
}
.panel h4 {
  margin: 0 0 12px;
  font-size: 16px;
  color: #303133;
}
</style>
