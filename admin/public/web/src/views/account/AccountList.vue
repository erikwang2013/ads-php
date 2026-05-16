<template>
  <div class="account-list">
    <div class="page-header">
      <h3>平台账户</h3>
      <el-button type="primary" @click="$router.push('/accounts/bind')">绑定新账户</el-button>
    </div>
    <el-table :data="accounts" v-loading="loading">
      <el-table-column label="平台" width="120">
        <template #default="{ row }"><PlatformBadge :platform="row.platform" /></template>
      </el-table-column>
      <el-table-column prop="account_name" label="账户名称" />
      <el-table-column prop="account_id_on_platform" label="平台账户ID" width="180" />
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '正常' : '已禁用' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="最近同步" width="180">
        <template #default="{ row }">{{ row.last_sync_at || '未同步' }}</template>
      </el-table-column>
      <el-table-column label="操作" width="160">
        <template #default="{ row }">
          <el-button size="small" @click="handleSync(row)">同步</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">解绑</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import PlatformBadge from '@/components/PlatformBadge.vue'
import { accountApi } from '@/api/account'

const accounts = ref<any[]>([])
const loading = ref(false)

async function fetchAccounts() { loading.value = true; const data = await accountApi.list(); accounts.value = data.list; loading.value = false }
async function handleSync(row: any) { await accountApi.sync(row.id); ElMessage.success('同步已触发') }
async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定要解绑该账户吗？', '提示', { type: 'warning' })
  await accountApi.destroy(row.id)
  ElMessage.success('已解绑')
  fetchAccounts()
}
onMounted(fetchAccounts)
</script>

<style scoped>
.account-list { background: #fff; border-radius: 8px; padding: 16px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h3 { margin: 0; }
</style>
