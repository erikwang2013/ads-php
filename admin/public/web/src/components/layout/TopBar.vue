<template>
  <div class="top-bar">
    <div class="left">
      <el-icon class="collapse-btn" @click="$emit('toggle')">
        <Fold v-if="!collapsed" /><Expand v-else />
      </el-icon>
      <span class="title">{{ title }}</span>
    </div>
    <div class="right">
      <span class="username">{{ authStore.user?.username }}</span>
      <el-button text @click="authStore.logout()">退出</el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Fold, Expand } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'

defineProps<{ collapsed: boolean }>()
defineEmits(['toggle'])
const authStore = useAuthStore()
const route = useRoute()
const title = computed(() => route.meta.title as string || '')
</script>

<style scoped>
.top-bar { height: 50px; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; background: #fff; border-bottom: 1px solid #e6e6e6; }
.left { display: flex; align-items: center; gap: 12px; }
.collapse-btn { cursor: pointer; font-size: 18px; }
.title { font-size: 16px; font-weight: 500; }
.right { display: flex; align-items: center; gap: 12px; }
</style>
