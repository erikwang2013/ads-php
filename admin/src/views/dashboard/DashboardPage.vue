<template>
  <div class="dashboard">
    <div class="metric-row">
      <MetricCard label="今日花费" :value="overview?.total_cost ?? 0" format="money" />
      <MetricCard label="展示量" :value="overview?.total_impressions ?? 0" format="number" />
      <MetricCard label="点击量" :value="overview?.total_clicks ?? 0" format="number" />
      <MetricCard label="点击率" :value="(overview?.avg_ctr ?? 0) / 100" format="percent" />
      <MetricCard label="转化率" :value="(overview?.avg_cvr ?? 0) / 100" format="percent" />
      <MetricCard label="平均CPA" :value="overview?.avg_cpa ?? 0" format="money" />
    </div>

    <div class="chart-section">
      <v-chart :option="trendOption" style="height:400px" autoresize />
    </div>

    <el-row :gutter="16">
      <el-col :span="12">
        <div class="panel">
          <h4>平台花费占比</h4>
          <v-chart :option="pieOption" style="height:300px" autoresize />
        </div>
      </el-col>
      <el-col :span="12">
        <div class="panel">
          <h4>TOP10 广告计划</h4>
          <el-table :data="topCampaigns" size="small" max-height="300">
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, PieChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import MetricCard from '@/components/MetricCard.vue'
import PlatformBadge from '@/components/PlatformBadge.vue'
import { formatFen } from '@/utils/format'
import { dashboardApi } from '@/api/dashboard'
import { campaignApi } from '@/api/campaign'

use([CanvasRenderer, LineChart, PieChart, GridComponent, TooltipComponent, LegendComponent])

const overview = ref<any>(null)
const byPlatform = ref<any[]>([])
const daily = ref<any[]>([])
const topCampaigns = ref<any[]>([])

const trendOption = computed(() => {
  const platforms = [...new Set(daily.value.map((d: any) => d.platform))] as string[]
  const dates = [...new Set(daily.value.map((d: any) => d.date))].sort() as string[]
  return {
    tooltip: { trigger: 'axis' },
    legend: { data: platforms },
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

const pieOption = computed(() => ({
  tooltip: { trigger: 'item' },
  series: [{ type: 'pie', radius: ['40%', '70%'], data: byPlatform.value.map((p: any) => ({ name: p.platform, value: p.cost })) }],
}))

onMounted(async () => {
  const data = await dashboardApi.summary()
  overview.value = data.overview
  byPlatform.value = data.by_platform
  daily.value = data.daily
  const campaigns = await campaignApi.list({ per_page: 10, sort: 'cost' })
  topCampaigns.value = campaigns.list
})
</script>

<style scoped>
.metric-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 16px; }
.chart-section { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
.panel { background: #fff; border-radius: 8px; padding: 16px; }
.panel h4 { margin: 0 0 12px; }
</style>
