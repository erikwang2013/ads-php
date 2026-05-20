<template>
  <div class="page-container">
    <div class="page-header">
      <h3>广告组</h3>
      <el-button type="primary" @click="showCreate = true">创建广告组</el-button>
    </div>

    <div class="filters">
      <el-select v-model="filter.platform" placeholder="平台" clearable style="width:140px" @change="fetchList">
        <el-option v-for="p in platforms" :key="p.code" :label="p.name" :value="p.code" />
      </el-select>
      <el-select v-model="filter.campaign_id" placeholder="所属计划" clearable style="width:200px" @change="fetchList">
        <el-option v-for="c in campaigns" :key="c.id" :label="c.name" :value="c.id" />
      </el-select>
      <el-select v-model="filter.status" placeholder="状态" clearable style="width:120px" @change="fetchList">
        <el-option label="投放中" value="enabled" /><el-option label="已暂停" value="paused" />
      </el-select>
    </div>

    <el-table :data="list" v-loading="loading">
      <el-table-column prop="name" label="广告组名称" min-width="180" show-overflow-tooltip />
      <el-table-column label="所属计划" min-width="140">
        <template #default="{ row }">{{ row.campaign_name }}</template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="row.status === 'enabled' ? 'success' : 'warning'" size="small">
            {{ row.status === 'enabled' ? '投放中' : '已暂停' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="出价金额" width="120" align="right">
        <template #default="{ row }">¥{{ (row.bid_amount / 100).toFixed(2) }}</template>
      </el-table-column>
      <el-table-column prop="bid_type" label="出价方式" width="100" />
      <el-table-column label="操作" width="200" align="center">
        <template #default="{ row }">
          <el-button size="small" @click="handleToggle(row)">{{ row.status === 'enabled' ? '暂停' : '启用' }}</el-button>
          <el-button size="small" type="primary" @click="showEdit(row)">编辑</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination v-model:current-page="pagination.page" v-model:page-size="pagination.perPage" :total="pagination.total"
      layout="total, sizes, prev, pager, next" style="margin-top:16px; justify-content:flex-end" @change="fetchList" />

    <el-dialog v-model="showCreate" :title="editing ? '编辑广告组' : '创建广告组'" width="560px">
      <el-form ref="formRef" :model="form" label-width="100px">
        <el-form-item label="所属计划" prop="campaign_id" v-if="!editing">
          <el-select v-model="form.campaign_id" style="width:100%">
            <el-option v-for="c in campaigns" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="广告组名称" prop="name">
          <el-input v-model="form.name" maxlength="100" />
        </el-form-item>
        <el-form-item label="出价金额">
          <el-input-number v-model="form.bid_amount" :min="0" :step="0.01" :precision="2" style="width:100%" />
          <span style="margin-left:8px;color:#909399">元</span>
        </el-form-item>
        <el-form-item label="出价方式">
          <el-select v-model="form.bid_type" style="width:100%">
            <el-option label="CPM" value="cpm" /><el-option label="CPC" value="cpc" /><el-option label="OCPM" value="ocpm" />
          </el-select>
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
import { adGroupApi } from '@/api/adgroup'
import { campaignApi } from '@/api/campaign'
import { platformApi } from '@/api/platform'

const loading = ref(false); const submitting = ref(false); const showCreate = ref(false)
const list = ref<any[]>([]); const platforms = ref<any[]>([]); const campaigns = ref<any[]>([])
const editing = ref<any>(null)
const filter = reactive({ platform: '', campaign_id: '', status: '' })
const pagination = reactive({ page: 1, perPage: 20, total: 0 })
const form = reactive({ campaign_id: undefined as number | undefined, name: '', bid_amount: 1, bid_type: 'cpc' })

async function fetchList() {
  loading.value = true
  const data = await adGroupApi.list({ ...filter, ...pagination })
  list.value = data.list; pagination.total = data.pagination.total; loading.value = false
}
async function handleToggle(row: any) {
  const enabled = row.status !== 'enabled'; await adGroupApi.toggle(row.id, enabled)
  ElMessage.success(enabled ? '已启用' : '已暂停'); fetchList()
}
function showEdit(row: any) { editing.value = row; form.name = row.name; form.bid_amount = row.bid_amount / 100; form.bid_type = row.bid_type; showCreate.value = true }
async function submitForm() {
  submitting.value = true
  try {
    if (editing.value) {
      await adGroupApi.update(editing.value.id, { name: form.name, bid_amount: Math.round(form.bid_amount * 100), bid_type: form.bid_type })
      ElMessage.success('更新成功')
    } else {
      await adGroupApi.create({ campaign_id: form.campaign_id, name: form.name, bid_amount: Math.round(form.bid_amount * 100), bid_type: form.bid_type })
      ElMessage.success('创建成功')
    }
    showCreate.value = false; editing.value = null; form.campaign_id = undefined; form.name = ''; form.bid_amount = 1; form.bid_type = 'cpc'
    fetchList()
  } finally { submitting.value = false }
}
onMounted(async () => {
  const [p, c] = await Promise.all([platformApi.list(), campaignApi.list({ per_page: 100 })])
  platforms.value = p; campaigns.value = c.list ?? []; fetchList()
})
</script>

<style scoped>
.page-container { background: #fff; border-radius: 8px; padding: 16px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h3 { margin: 0; }
.filters { display: flex; gap: 12px; margin-bottom: 16px; }
</style>
