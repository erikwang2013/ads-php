<template>
  <div class="page-container">
    <div class="page-header"><h3>投放日历</h3></div>
    <div class="filters" style="margin-bottom:16px">
      <el-date-picker v-model="dateRange" type="monthrange" range-separator="至" start-placeholder="起始月" end-placeholder="结束月" @change="loadData" style="width:240px" />
      <el-select v-model="filter.platform" placeholder="平台" clearable style="width:140px" @change="loadData">
        <el-option v-for="p in platforms" :key="p.code" :label="p.name" :value="p.code" />
      </el-select>
    </div>

    <div class="gantt-container" v-loading="loading">
      <div class="gantt-header">
        <div class="gantt-label" style="min-width:180px">计划名称</div>
        <div class="gantt-timeline" ref="timelineRef">
          <div v-for="(d,i) in dayHeaders" :key="i" :style="{ minWidth: cellWidth+'px' }" class="gantt-day-header">
            {{ d }}
          </div>
        </div>
      </div>
      <div v-for="event in events" :key="event.id" class="gantt-row">
        <div class="gantt-label" style="min-width:180px;font-size:13px">
          <span :style="{ color: platformColor(event.platform), fontWeight:'500' }">{{ event.name }}</span>
          <small style="color:#999;display:block">{{ event.platform }}</small>
        </div>
        <div class="gantt-timeline" :style="{ position:'relative' }">
          <div
            :style="{
              position:'absolute',
              left: barLeft(event) + 'px',
              width: barWidth(event) + 'px',
              top: '8px',
              height: '20px',
              borderRadius: '10px',
              background: platformColor(event.platform),
              opacity: event.status === 'enabled' ? 0.9 : 0.4,
            }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { api } from '@/api/index'
import { platformApi } from '@/api/platform'

const loading = ref(false); const platforms = ref<any[]>([])
const events = ref<any[]>([])
const dateRange = ref<[Date, Date]>([new Date(new Date().getFullYear(), new Date().getMonth(), 1), new Date(new Date().getFullYear(), new Date().getMonth()+1, 0)])
const filter = reactive({ platform: '' })
const cellWidth = 32

function platformColor(p: string): string {
  return ({ juliang: '#e74c3c', baidu: '#3498db', tencent: '#2ecc71', meta: '#1877f2', google: '#ea4335' } as any)[p] || '#667eea'
}

const startDate = computed(() => {
  const s = dateRange.value?.[0]; return s ? s.toISOString().slice(0,10) : ''
})
const endDate = computed(() => {
  const e = dateRange.value?.[1]; return e ? e.toISOString().slice(0,10) : ''
})
const dayHeaders = computed(() => {
  if (!dateRange.value?.[0] || !dateRange.value?.[1]) return []
  const days: string[] = []; const d = new Date(dateRange.value[0])
  while (d <= dateRange.value[1]) { days.push(d.toISOString().slice(8,10)); d.setDate(d.getDate()+1) }
  return days
})
const totalDays = computed(() => dayHeaders.value.length)

function barLeft(e: any): number {
  if (!dateRange.value?.[0] || !e.start_date) return 0
  const start = new Date(dateRange.value[0]); const eStart = new Date(e.start_date)
  return Math.max(0, (eStart.getTime() - start.getTime()) / 86400000) * cellWidth
}
function barWidth(e: any): number {
  if (!e.start_date || !e.end_date) return cellWidth * 7
  const eStart = new Date(e.start_date); const eEnd = new Date(e.end_date)
  return Math.max(cellWidth, ((eEnd.getTime() - eStart.getTime()) / 86400000 + 1) * cellWidth)
}

async function loadData() {
  loading.value = true
  try {
    const data = await api.get('/reports/calendar', { params: { date_start: startDate.value, date_end: endDate.value, ...filter } })
    events.value = data as any[]
  } finally { loading.value = false }
}
onMounted(async () => { platforms.value = await platformApi.list(); loadData() })
</script>

<style scoped>
.page-container { background: #fff; border-radius: 8px; padding: 16px; overflow-x: auto; }
.page-header { margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
.page-header h3 { margin: 0; }
.gantt-container { min-width: 800px; }
.gantt-header, .gantt-row { display: flex; align-items: center; border-bottom: 1px solid #eee; }
.gantt-row { min-height: 44px; }
.gantt-label { padding: 8px; font-size: 12px; color: #666; flex-shrink: 0; }
.gantt-timeline { flex: 1; display: flex; overflow-x: auto; height: 36px; position: relative; }
.gantt-day-header { text-align: center; font-size: 11px; color: #999; line-height: 36px; border-right: 1px solid #f0f0f0; }
</style>
