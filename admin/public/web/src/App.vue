<template>
  <router-view />
</template>

<script setup lang="ts">
import { useAlertStore } from '@/stores/alert'
import { onMounted, onUnmounted } from 'vue'

const alertStore = useAlertStore()

onMounted(() => alertStore.startPolling())
onUnmounted(() => alertStore.stopPolling())
</script>
