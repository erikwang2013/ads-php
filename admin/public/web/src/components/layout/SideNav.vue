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
    <el-menu-item index="/accounts">
      <el-icon><User /></el-icon>
      <span>账户管理</span>
    </el-menu-item>
    <el-sub-menu index="system">
      <template #title>
        <el-icon><Setting /></el-icon>
        <span>系统管理</span>
      </template>
      <el-menu-item index="/sync">同步状态</el-menu-item>
      <el-menu-item index="/system/users">用户管理</el-menu-item>
      <el-menu-item index="/system/audit">审计日志</el-menu-item>
    </el-sub-menu>
  </el-menu>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import { DataAnalysis, User, Setting } from '@element-plus/icons-vue'
defineProps<{ collapsed: boolean }>()
const route = useRoute()
</script>
