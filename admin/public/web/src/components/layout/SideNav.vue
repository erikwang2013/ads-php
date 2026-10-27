<template>
  <el-menu
    :default-active="route.path"
    router
    :collapse="collapsed"
    background-color="#304156"
    text-color="#bfcbd9"
    active-text-color="#409EFF"
  >
    <el-menu-item index="/dashboard">
      <el-icon><DataAnalysis /></el-icon>
      <span>仪表盘</span>
    </el-menu-item>
    <el-menu-item index="/docs" @click="openDocs">
      <el-icon><Document /></el-icon>
      <span>API 文档</span>
    </el-menu-item>
    <el-sub-menu index="ads">
      <template #title>
        <el-icon><Promotion /></el-icon>
        <span>广告管理</span>
      </template>
      <el-menu-item index="/campaigns">广告计划</el-menu-item>
      <el-menu-item index="/adgroups">广告组</el-menu-item>
      <el-menu-item index="/creatives">广告创意</el-menu-item>
    </el-sub-menu>
    <el-sub-menu index="reports">
      <template #title>
        <el-icon><DataAnalysis /></el-icon>
        <span>数据报表</span>
      </template>
      <el-menu-item index="/reports/view">报表分析</el-menu-item>
      <el-menu-item index="/reports/export">报表导出</el-menu-item>
      <el-menu-item index="/reports/calendar">投放日历</el-menu-item>
      <el-menu-item index="/reports/attribution">归因分析</el-menu-item>
    </el-sub-menu>
    <el-menu-item index="/notifications">
      <el-icon><Bell /></el-icon>
      <span>通知中心</span>
      <el-badge v-if="notifStore.unreadCount > 0" :value="notifStore.unreadCount" class="nav-badge" />
    </el-menu-item>
    <el-menu-item index="/accounts">
      <el-icon><User /></el-icon>
      <span>账户管理</span>
    </el-menu-item>
    <el-sub-menu index="alerts">
      <template #title>
        <el-icon><Bell /></el-icon>
        <span>告警管理</span>
        <el-badge v-if="alertStore.unreadCount > 0" :value="alertStore.unreadCount" class="nav-badge" />
      </template>
      <el-menu-item index="/alerts">告警规则</el-menu-item>
      <el-menu-item index="/alerts/logs">告警记录</el-menu-item>
    </el-sub-menu>
    <el-sub-menu index="system">
      <template #title>
        <el-icon><Setting /></el-icon>
        <span>系统管理</span>
      </template>
      <el-menu-item index="/sync">同步状态</el-menu-item>
      <el-menu-item index="/system/users">用户管理</el-menu-item>
      <el-menu-item index="/system/audit">审计日志</el-menu-item>
      <el-menu-item index="/system/cdn">CDN 服务商</el-menu-item>
    </el-sub-menu>
  </el-menu>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import { DataAnalysis, Promotion, User, Bell, Document, Setting } from '@element-plus/icons-vue'
import { useAlertStore } from '@/stores/alert'
import { useNotificationStore } from '@/stores/notification'
defineProps<{ collapsed: boolean }>()
const route = useRoute()
const alertStore = useAlertStore()
const notifStore = useNotificationStore()

function openDocs() {
  window.open('/docs', '_blank')
}
</script>

<style scoped>
.nav-badge {
  margin-left: 8px;
}
.nav-badge :deep(.el-badge__content) {
  position: static;
  transform: none;
}
</style>
