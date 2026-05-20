<template>
  <div class="page-container">
    <div class="page-header"><h3>报表分析</h3></div>

    <div class="filters">
      <el-date-picker v-model="dateRange" type="daterange" range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期"
        style="width:260px" @change="loadData" />
      <el-select v-model="filter.platform" placeholder="平台" clearable style="width:140px" @change="loadData">
        <el-option v-for="p in platforms" :key="p.code" :label="p.name" :value="p.code" />
      </el-select>
      <el-select v-model="filter.dimension" placeholder="维度" style="width:120px" @change="loadData">
        <el-option label="按日期" value="date" /><el-option label="按平台" value="platform" /><el-option label="按计划" value="campaign" />
      </el-select>
      <el-select v-model="filter.metrics" placeholder="指标" multiple style="width:240px" @change="loadData">
        <el-option label="花费" value="cost" /><el-option label="展示" value="impressions" />
        <el-option label="点击" value="clicks" /><el-option label="转化" value="conversions" />
        <el-option label="CTR" value="ctr" /><el-option label="CVR" value="cvr" />
      </el-select>
    </div>

    <div class="chart-grid">
      <div class="chart-item"><v-chart :option="trendOption" autoresize style="height:360px" /></div>
      <div class="chart-item"><v-chart :option="barOption" autoresize style="height:360px" /></div>
    </div>

    <el-table :data="tableData" v-loading="loading" style="margin-top:16px">
      <el-table-column v-for="col in tableColumns" :key="col.prop" :prop="col.prop" :label="col.label" :min-width="col.width || 120" align="right" />
    </el-table>

    <el-pagination v-if="pagination.total > 0" v-model:current-page="pagination.page" v-model:page-size="pagination.perPage"
      :total="pagination.total" layout="total, sizes, prev, pager, next" style="margin-top:16px; justify-content:flex-end" @change="loadData" />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, BarChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import { ElMessage } from 'element-plus'
import { reportApi } from '@/api/export'
import { platformApi } from '@/api/platform'

use([CanvasRenderer, LineChart, BarChart, GridComponent, TooltipComponent, LegendComponent])

const loading = ref(false)
const platforms = ref<any[]>([])
const dateRange = ref<[Date, Date]>([new Date(Date.now() - 7 * 86400000), new Date()])
const filter = reactive({ platform: '', dimension: 'date', metrics: ['cost', 'impressions', 'clicks'] })
const pagination = reactive({ page: 1, perPage: 20, total: 0 })
const tableData = ref<any[]>([]); const tableColumns = ref<any[]>([])

const trendOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { data: filter.metrics },
  xAxis: { type: 'category', data: tableData.value.map((r: any) => r.date || r.platform || r.campaign_name || '') },
  yAxis: { type: 'value' },
  series: filter.metrics.map((m: string) => ({ name: m, type: 'line', data: tableData.value.map((r: any) => r[m] ?? 0), smooth: true })),
}))

const barOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { data: filter.metrics },
  grid: { left: '3%', right: '4%', containLabel: true },
  xAxis: { type: 'value' },
  yAxis: { type: 'category', data: tableData.value.map((r: any) => r.date || r.platform || r.campaign_name || '').reverse() },
  series: filter.metrics.map((m: string) => ({ name: m, type: 'bar', data: tableData.value.map((r: any) => r[m] ?? 0).reverse(), barMaxWidth: 24 })),
}))

async function loadData() {
  loading.value = true
  try {
    const [start, end] = dateRange.value
    const params: Record<string, any> = {
      dimensions: [filter.dimension],
      metrics: filter.metrics,
      date_start: start.toISOString().slice(0, 10),
      date_end: end.toISOString().slice(0, 10),
    }
    if (filter.platform) params.platform = filter.platform

    const data = await reportApi.custom(params)
    tableData.value = data.list || []
    tableColumns.value = [
      { prop: filter.dimension === 'date' ? 'date' : filter.dimension === 'platform' ? 'platform' : 'campaign_name', label: '维度', width: 140 },
      ...filter.metrics.map((m: string) => ({ prop: m, label: m })),
    ]
  } catch { ElMessage.error('加载报表失败') } finally { loading.value = false }
}

onMounted(async () => { platforms.value = await platformApi.list(); loadData() })
</script>

<style scoped>
.page-container { background: #fff; border-radius: 8px; padding: 16px; }
.page-header { margin-bottom: 16px; }
.page-header h3 { margin: 0; }
.filters { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.chart-item { background: #fafafa; border-radius: 8px; padding: 12px; }
@media (max-width: 1024px) { .chart-grid { grid-template-columns: 1fr; } }
</style>
