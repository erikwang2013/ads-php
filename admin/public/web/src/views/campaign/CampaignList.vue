<template>
  <div class="campaign-list">
    <div class="page-header">
      <h3>广告计划</h3>
      <el-button type="primary" @click="showCreate = true">创建计划</el-button>
    </div>

    <div class="filters">
      <el-select v-model="filter.platform" placeholder="平台" clearable style="width:140px" @change="fetchList">
        <el-option v-for="p in platforms" :key="p.code" :label="p.name" :value="p.code" />
      </el-select>
      <el-select v-model="filter.status" placeholder="状态" clearable style="width:120px" @change="fetchList">
        <el-option label="投放中" value="enabled" /><el-option label="已暂停" value="paused" />
      </el-select>
      <el-input v-model="filter.keyword" placeholder="搜索计划名称" clearable style="width:220px" @change="fetchList" />
    </div>

    <el-table :data="list" v-loading="loading" @selection-change="(rows:any) => selectedRows = rows">
      <el-table-column type="selection" width="40" />
      <el-table-column prop="name" label="计划名称" min-width="180" show-overflow-tooltip />
      <el-table-column label="平台" width="100">
        <template #default="{ row }"><PlatformBadge :platform="row.platform" /></template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="row.status === 'enabled' ? 'success' : 'warning'" size="small">
            {{ row.status === 'enabled' ? '投放中' : '已暂停' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="日预算" width="120" align="right">
        <template #default="{ row }">¥{{ formatFen(row.daily_budget) }}</template>
      </el-table-column>
      <el-table-column label="今日花费" width="120" align="right">
        <template #default="{ row }">¥{{ formatFen(row.today_cost ?? 0) }}</template>
      </el-table-column>
      <el-table-column label="操作" width="200" align="center">
        <template #default="{ row }">
          <el-button size="small" @click="handleToggle(row)">{{ row.status === 'enabled' ? '暂停' : '启用' }}</el-button>
          <el-button size="small" type="primary" @click="showEdit(row)">编辑</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="batch-actions" v-if="selectedRows.length > 0">
      <span>已选 {{ selectedRows.length }} 项</span>
      <el-button size="small" @click="batchToggle(true)">批量启用</el-button>
      <el-button size="small" @click="batchToggle(false)">批量暂停</el-button>
    </div>

    <el-pagination v-model:current-page="pagination.page" v-model:page-size="pagination.perPage" :total="pagination.total"
      layout="total, sizes, prev, pager, next" style="margin-top:16px; justify-content:flex-end" @change="fetchList" />

    <!-- Create/Edit Dialog -->
    <el-dialog v-model="showCreate" :title="editing ? '编辑计划' : '创建计划'" width="560px">
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px">
        <el-form-item label="投放平台" prop="platform" v-if="!editing">
          <el-select v-model="form.platform" style="width:100%">
            <el-option v-for="p in platforms" :key="p.code" :label="p.name" :value="p.code" />
          </el-select>
        </el-form-item>
        <el-form-item label="平台账户" prop="platform_account_id" v-if="!editing">
          <el-select v-model="form.platform_account_id" style="width:100%">
            <el-option v-for="a in accounts" :key="a.id" :label="a.account_name" :value="a.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="计划名称" prop="name">
          <el-input v-model="form.name" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="日预算">
          <el-input-number v-model="form.daily_budget" :min="0" :step="100" style="width:100%" />
          <span style="margin-left:8px;color:#909399">元</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showCreate = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import PlatformBadge from '@/components/PlatformBadge.vue'
import { formatFen } from '@/utils/format'
import { campaignApi } from '@/api/campaign'
import { platformApi } from '@/api/platform'
import { accountApi } from '@/api/account'
import { useConfirmStore } from '@/stores/confirm'

const confirmStore = useConfirmStore()
const loading = ref(false); const submitting = ref(false); const showCreate = ref(false)
const list = ref<any[]>([]); const platforms = ref<any[]>([]); const accounts = ref<any[]>([])
const selectedRows = ref<any[]>([]); const editing = ref<any>(null)
const filter = reactive({ platform: '', status: '', keyword: '' })
const pagination = reactive({ page: 1, perPage: 20, total: 0 })
const form = reactive({ platform: '', platform_account_id: undefined as number | undefined, name: '', daily_budget: 200 })
const formRules = {
  platform: [{ required: true, message: '请选择平台', trigger: 'change' }],
  platform_account_id: [{ required: true, message: '请选择账户', trigger: 'change' }],
  name: [{ required: true, message: '请输入计划名称', trigger: 'blur' }],
}

async function fetchList() {
  loading.value = true
  const data = await campaignApi.list({ ...filter, ...pagination })
  list.value = data.list; pagination.total = data.pagination.total; loading.value = false
}
async function handleToggle(row: any) {
  const enabled = row.status !== 'enabled'; await campaignApi.toggle(row.id, enabled)
  ElMessage.success(enabled ? '已启用' : '已暂停'); fetchList()
}
function batchToggle(enabled: boolean) {
  const actionLabel = enabled ? '批量启用' : '批量暂停'
  confirmStore.show({
    title: actionLabel,
    message: `确定要${actionLabel}选中的 ${selectedRows.value.length} 个广告计划吗？`,
    confirmWord: enabled ? 'ENABLE' : 'PAUSE',
    confirmText: actionLabel,
    onConfirm: async () => {
      const ids = selectedRows.value.map((r: any) => r.id)
      const result = await campaignApi.batchToggle(ids, enabled)
      ElMessage.success(`${actionLabel}完成：成功 ${result.success}，失败 ${result.failed}`)
      selectedRows.value = []; fetchList()
    },
  })
}
function showEdit(row: any) { editing.value = row; form.name = row.name; form.daily_budget = row.daily_budget / 100; showCreate.value = true }
async function submitForm() {
  submitting.value = true
  try {
    if (editing.value) {
      await campaignApi.update(editing.value.id, { name: form.name, daily_budget: form.daily_budget * 100 })
      ElMessage.success('更新成功')
    } else {
      await campaignApi.create({ platform: form.platform, platform_account_id: form.platform_account_id, name: form.name, daily_budget: form.daily_budget * 100 })
      ElMessage.success('创建成功')
    }
    showCreate.value = false; editing.value = null; form.platform = ''; form.platform_account_id = undefined; form.name = ''; form.daily_budget = 200
    fetchList()
  } finally { submitting.value = false }
}
onMounted(async () => {
  const [p, a] = await Promise.all([platformApi.list(), accountApi.list()])
  platforms.value = p; accounts.value = a.list ?? []; fetchList()
})
</script>

<style scoped>
.campaign-list { background: #fff; border-radius: 8px; padding: 16px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h3 { margin: 0; }
.filters { display: flex; gap: 12px; margin-bottom: 16px; }
.batch-actions { margin-top: 12px; padding: 8px 12px; background: #f0f9ff; border-radius: 4px; display: flex; align-items: center; gap: 12px; }
</style>
