<script setup lang="ts">
import { useAlertStore } from '@/stores/alert'
import { onMounted, onUnmounted } from 'vue'
import GlobalConfirm from '@/components/GlobalConfirm.vue'

const alertStore = useAlertStore()

onMounted(() => alertStore.startPolling())
onUnmounted(() => alertStore.stopPolling())
</script>

<template>
  <GlobalConfirm />
  <router-view />
</template>
