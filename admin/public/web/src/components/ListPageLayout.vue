<template>
  <div class="list-page">
    <div class="page-header">
      <h3><slot name="title" /></h3>
      <slot name="header-actions" />
    </div>

    <div v-if="$slots.filters" class="filters">
      <slot name="filters" />
    </div>

    <slot name="table" />

    <div v-if="$slots['batch-actions']" class="batch-actions">
      <slot name="batch-actions" />
    </div>

    <div v-if="showPagination !== false" class="list-pagination">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="currentPerPage"
        :total="total"
        layout="total, sizes, prev, pager, next"
        @change="$emit('page-change')"
      />
    </div>

    <slot name="dialog" />
  </div>
</template>

<script setup lang="ts">
defineProps<{
  total: number
  showPagination?: boolean
}>()

defineEmits(['page-change'])

const currentPage = defineModel<number>('page', { default: 1 })
const currentPerPage = defineModel<number>('perPage', { default: 20 })
</script>

<style scoped>
.list-page { background: #fff; border-radius: 8px; padding: 16px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h3 { margin: 0; }
.filters { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.batch-actions { margin-top: 12px; padding: 8px 12px; background: #f0f9ff; border-radius: 4px; display: flex; align-items: center; gap: 12px; }
.list-pagination { margin-top: 16px; display: flex; justify-content: flex-end; }
</style>
