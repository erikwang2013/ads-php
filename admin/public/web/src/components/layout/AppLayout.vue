<template>
  <div class="app-layout">
    <div class="sidebar" :style="{ width: collapsed ? '64px' : '200px' }">
      <SideNav :collapsed="collapsed" />
    </div>
    <div class="main">
      <TopBar :collapsed="collapsed" @toggle="collapsed = !collapsed" />
      <div class="content">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import SideNav from './SideNav.vue'
import TopBar from './TopBar.vue'
const collapsed = ref(false)
</script>

<style scoped>
.app-layout { display: flex; min-height: 100vh; }
.sidebar { background: #304156; transition: width 0.3s; overflow: hidden; }
.main { flex: 1; display: flex; flex-direction: column; background: #f0f2f5; }
.content { flex: 1; padding: 16px; }
</style>
