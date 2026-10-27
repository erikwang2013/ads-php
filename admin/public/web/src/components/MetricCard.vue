<template>
  <div class="metric-card">
    <div class="label">{{ label }}</div>
    <div class="value">{{ formatted }}</div>
    <div v-if="trend !== undefined" class="trend" :class="trend >= 0 ? 'up' : 'down'">
      <span>{{ trend >= 0 ? '↑' : '↓' }}</span>
      {{ Math.abs(trend).toFixed(1) }}%
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ label: string; value: number; format: 'money' | 'number' | 'percent'; trend?: number }>()
const formatted = computed(() => {
  switch (props.format) {
    case 'money': return '¥' + (props.value / 100).toFixed(2)
    case 'number': return props.value.toLocaleString()
    case 'percent': return (props.value * 100).toFixed(2) + '%'
    default: return String(props.value)
  }
})
</script>

<style scoped>
.metric-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.label { font-size: 14px; color: #909399; margin-bottom: 8px; }
.value { font-size: 28px; font-weight: 600; color: #303133; }
.trend { font-size: 12px; margin-top: 6px; }
.trend.up { color: #67C23A; } .trend.down { color: #F56C6C; }
</style>
