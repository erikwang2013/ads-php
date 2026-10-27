<template>
  <ListPageLayout v-model:page="pagination.page" v-model:per-page="pagination.perPage" :total="pagination.total" @page-change="fetchList">
    <template #title>自动出价规则</template>
    <template #header-actions><el-button type="primary" @click="showCreate = true">创建规则</el-button></template>
    <template #filters>
      <el-select v-model="filter.platform" placeholder="平台" clearable style="width:140px" @change="fetchList">
        <el-option v-for="p in platforms" :key="p.code" :label="p.name" :value="p.code" />
      </el-select>
      <el-select v-model="filter.enabled" placeholder="状态" clearable style="width:100px" @change="fetchList">
        <el-option label="启用" :value="1" /><el-option label="禁用" :value="0" />
      </el-select>
    </template>
    <template #table>
      <el-table :data="list" v-loading="loading">
        <el-table-column prop="name" label="规则名称" min-width="180" show-overflow-tooltip />
        <el-table-column prop="metric" label="指标" width="100" />
        <el-table-column label="条件" width="120">
          <template #default="{ row }">{{ row.condition }} {{ row.threshold }}</template>
        </el-table-column>
        <el-table-column label="动作" width="140">
          <template #default="{ row }">
            <el-tag size="small">{{ actionLabel(row.action_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="cooldown_minutes" label="冷却(分)" width="90" />
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.enabled ? 'success' : 'info'" size="small">{{ row.enabled ? '启用' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140" align="center">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="showEdit(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </template>
    <template #dialog>
      <el-dialog v-model="showCreate" :title="editing ? '编辑规则' : '创建规则'" width="560px">
        <el-form :model="form" label-width="110px">
          <el-form-item label="规则名称"><el-input v-model="form.name" maxlength="100" /></el-form-item>
          <el-form-item label="监控指标">
            <el-select v-model="form.metric" style="width:100%">
              <el-option v-for="m in metrics" :key="m.value" :label="m.label" :value="m.value" />
            </el-select>
          </el-form-item>
          <el-form-item label="触发条件">
            <el-select v-model="form.condition" style="width:120px">
              <el-option label="大于" value="gt" /><el-option label="大于等于" value="gte" /><el-option label="小于" value="lt" /><el-option label="小于等于" value="lte" />
            </el-select>
            <el-input-number v-model="form.threshold" :min="0" :precision="2" style="width:160px; margin-left:8px" />
          </el-form-item>
          <el-form-item label="执行动作">
            <el-select v-model="form.action_type" style="width:100%">
              <el-option label="调整预算" value="adjust_budget" /><el-option label="暂停广告" value="toggle_pause" /><el-option label="启用广告" value="toggle_enable" />
            </el-select>
          </el-form-item>
          <el-form-item v-if="form.action_type === 'adjust_budget'" label="调整步长(分)">
            <el-input-number v-model="form.adjust_step" style="width:100%" />
          </el-form-item>
          <el-form-item label="冷却时间(分)">
            <el-input-number v-model="form.cooldown_minutes" :min="5" :max="1440" style="width:100%" />
          </el-form-item>
          <el-form-item label="启用"><el-switch v-model="form.enabled" :active-value="1" :inactive-value="0" /></el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="showCreate = false">取消</el-button>
          <el-button type="primary" :loading="submitting" @click="submitForm">确定</el-button>
        </template>
      </el-dialog>
    </template>
  </ListPageLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import ListPageLayout from '@/components/ListPageLayout.vue'
import { api } from '@/api/index'
import { platformApi } from '@/api/platform'

const bidApi = {
  list(p: any) { return api.get('/bid-rules', { params: p }) },
  create(d: any) { return api.post('/bid-rules', d) },
  update(id: number, d: any) { return api.put(`/bid-rules/${id}`, d) },
  destroy(id: number) { return api.delete(`/bid-rules/${id}`) },
}

const metrics = [
  { value: 'cost', label: '花费' }, { value: 'impressions', label: '展示量' }, { value: 'clicks', label: '点击量' },
  { value: 'conversions', label: '转化数' }, { value: 'ctr', label: 'CTR' }, { value: 'cvr', label: 'CVR' }, { value: 'roi', label: 'ROI' },
]
function actionLabel(t: string) { return ({ adjust_budget: '调整预算', toggle_pause: '暂停', toggle_enable: '启用' } as any)[t] || t }

const loading = ref(false); const submitting = ref(false); const showCreate = ref(false)
const list = ref<any[]>([]); const platforms = ref<any[]>([]); const editing = ref<any>(null)
const filter = reactive({ platform: '', enabled: undefined as number | undefined })
const pagination = reactive({ page: 1, perPage: 20, total: 0 })
const form = reactive({ name: '', metric: 'cost', condition: 'gt' as string, threshold: 1000, action_type: 'adjust_budget', adjust_step: 0, cooldown_minutes: 60, enabled: 1 as number })

async function fetchList() { loading.value = true; const data = await bidApi.list({ ...filter, ...pagination }); list.value = data.list; pagination.total = data.pagination.total; loading.value = false }
function showEdit(row: any) { editing.value = row; Object.assign(form, row); showCreate.value = true }
async function handleDelete(row: any) { await ElMessageBox.confirm('确定删除该规则？', '提示', { type: 'warning' }); await bidApi.destroy(row.id); ElMessage.success('已删除'); fetchList() }
async function submitForm() {
  submitting.value = true
  try {
    if (editing.value) { await bidApi.update(editing.value.id, form); ElMessage.success('更新成功') }
    else { await bidApi.create(form); ElMessage.success('创建成功') }
    showCreate.value = false; editing.value = null; Object.assign(form, { name: '', metric: 'cost', condition: 'gt', threshold: 1000, action_type: 'adjust_budget', adjust_step: 0, cooldown_minutes: 60, enabled: 1 })
    fetchList()
  } finally { submitting.value = false }
}
onMounted(async () => { platforms.value = await platformApi.list(); fetchList() })
</script>
