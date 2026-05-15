<template>
  <span class="platform-badge" :style="{ background: bg, color: color }"><span class="flag">{{ flag }}</span> {{ name }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ platform: string }>()
const flagMap: Record<string, string> = {
  juliang: '🇨🇳', baidu: '🇨🇳', taobao: '🇨🇳', tencent: '🇨🇳',
  kuaishou: '🇨🇳', xiaohongshu: '🇨🇳', weibo: '🇨🇳', bilibili: '🇨🇳',
  youku: '🇨🇳', meituan: '🇨🇳', zhihu: '🇨🇳', qihoo360: '🇨🇳',
  sogou: '🇨🇳', umeng: '🇨🇳', jingdong: '🇨🇳', pinduoduo: '🇨🇳',
  google: '🇺🇸', youtube: '🇺🇸', meta: '🇺🇸', tiktok: '🇸🇬',
  linkedin: '🇺🇸', snapchat: '🇺🇸', pinterest: '🇺🇸', twitter: '🇺🇸',
  amazon: '🇺🇸', thetradedesk: '🇺🇸', spotify: '🇸🇪', twitch: '🇺🇸', netflix: '🇺🇸',
}
const map: Record<string, { name: string; bg: string; color: string }> = {
  juliang: { name: '巨量引擎', bg: '#e6f0ff', color: '#2979ff' },
  baidu:   { name: '百度',     bg: '#e8f5e9', color: '#4caf50' },
  taobao:  { name: '淘宝',     bg: '#fff3e0', color: '#ff9800' },
  google:  { name: 'Google',   bg: '#e8eaf6', color: '#4285f4' },
  tiktok:  { name: 'TikTok',   bg: '#fce4ec', color: '#ff0050' },
}
const info = computed(() => map[props.platform] ?? { name: props.platform, bg: '#f5f5f5', color: '#666' })
const flag = computed(() => flagMap[props.platform] ?? '')
const name = computed(() => info.value.name)
const bg = computed(() => info.value.bg)
const color = computed(() => info.value.color)
</script>

<style scoped>
.platform-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
</style>
