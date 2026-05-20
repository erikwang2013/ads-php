<template>
  <ListPageLayout v-model:page="pagination.page" v-model:per-page="pagination.perPage" :total="pagination.total" @page-change="fetchList">
    <template #title>通知中心</template>
    <template #header-actions><el-button type="primary" @click="markAll">全部已读</el-button></template>
    <template #filters>
      <el-select v-model="filter.type" placeholder="类型" clearable style="width:120px" @change="fetchList">
        <el-option label="告警" value="alert" /><el-option label="系统" value="system" />
      </el-select>
      <el-select v-model="filter.is_read" placeholder="状态" clearable style="width:100px" @change="fetchList">
        <el-option label="未读" :value="0" /><el-option label="已读" :value="1" />
      </el-select>
    </template>
    <template #table>
      <el-table :data="list" v-loading="loading">
        <el-table-column label="类型" width="80"><template #default="{ row }"><el-tag :type="row.type === 'alert' ? 'danger' : 'info'" size="small">{{ row.type }}</el-tag></template></el-table-column>
        <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
        <el-table-column label="内容" min-width="240" show-overflow-tooltip><template #default="{ row }">{{ truncate(row.content, 80) }}</template></el-table-column>
        <el-table-column label="状态" width="80"><template #default="{ row }"><span :style="{ color: row.is_read ? '#909399' : '#e6a23c' }">{{ row.is_read ? '已读' : '未读' }}</span></template></el-table-column>
        <el-table-column prop="created_at" label="时间" width="170" />
        <el-table-column label="操作" width="100" align="center"><template #default="{ row }"><el-button v-if="!row.is_read" size="small" text type="primary" @click="markOne(row.id)">标为已读</el-button></template></el-table-column>
      </el-table>
    </template>
  </ListPageLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import ListPageLayout from '@/components/ListPageLayout.vue'
import { notificationApi } from '@/api/notification'

const loading = ref(false)
const list = ref<any[]>([])
const filter = reactive({ type: '', is_read: undefined as number | undefined })
const pagination = reactive({ page: 1, perPage: 20, total: 0 })

function truncate(text: string | null, max: number): string { if (!text) return ''; return text.length > max ? text.slice(0, max) + '...' : text }
async function fetchList() { loading.value = true; const data = await notificationApi.list({ ...filter, ...pagination }); list.value = data.list; pagination.total = data.pagination.total; loading.value = false }
async function markOne(id: number) { await notificationApi.markRead(id); ElMessage.success('已标为已读'); fetchList() }
async function markAll() { await notificationApi.markAllRead(); ElMessage.success('全部已读'); fetchList() }
onMounted(() => fetchList())
</script>
