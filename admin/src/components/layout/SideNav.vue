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
    <el-sub-menu index="ads">
      <template #title>
        <el-icon><Promotion /></el-icon>
        <span>广告管理</span>
      </template>
      <el-menu-item index="/campaigns">广告计划</el-menu-item>
    </el-sub-menu>
    <el-menu-item index="/accounts">
      <el-icon><User /></el-icon>
      <span>账户管理</span>
    </el-menu-item>
  </el-menu>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import { DataAnalysis, Promotion, User } from '@element-plus/icons-vue'
defineProps<{ collapsed: boolean }>()
const route = useRoute()
</script>
