<template>
  <div class="alert-rule-list">
    <div class="page-header">
      <h3>告警规则</h3>
      <el-button type="primary" @click="openCreate">创建规则</el-button>
    </div>

    <div class="filters">
      <el-select v-model="filter.metric" placeholder="指标" clearable style="width:140px" @change="fetchList">
        <el-option v-for="m in metricOptions" :key="m.value" :label="m.label" :value="m.value" />
      </el-select>
      <el-select v-model="filter.platform" placeholder="平台" clearable style="width:140px" @change="fetchList">
        <el-option v-for="p in platforms" :key="p.code" :label="p.name" :value="p.code" />
      </el-select>
      <el-select v-model="filter.enabled" placeholder="状态" clearable style="width:120px" @change="fetchList">
        <el-option label="启用" :value="1" />
        <el-option label="禁用" :value="0" />
      </el-select>
    </div>

    <el-table :data="list" v-loading="loading" stripe>
      <el-table-column prop="name" label="名称" min-width="160" show-overflow-tooltip />
      <el-table-column label="指标" width="120">
        <template #default="{ row }">
          <el-tag size="small">{{ metricLabel(row.metric) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="条件" width="90" align="center">
        <template #default="{ row }">{{ conditionLabel(row.condition) }} {{ row.threshold }}</template>
      </el-table-column>
      <el-table-column label="范围" width="110" align="center">
        <template #default="{ row }">{{ scopeLabel(row.scope) }}</template>
      </el-table-column>
      <el-table-column label="检查间隔" width="100" align="center">
        <template #default="{ row }">{{ row.check_interval }}分钟</template>
      </el-table-column>
      <el-table-column label="状态" width="80" align="center">
        <template #default="{ row }">
          <el-switch
            :model-value="row.enabled"
            :active-value="true"
            :inactive-value="false"
            @change="(val: boolean) => handleToggle(row, val)"
          />
        </template>
      </el-table-column>
      <el-table-column label="操作" width="160" align="center" fixed="right">
        <template #default="{ row }">
          <el-button size="small" type="primary" link @click="openEdit(row)">编辑</el-button>
          <el-button size="small" type="danger" link @click="handleDeleteClick(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="pagination.page"
      v-model:page-size="pagination.perPage"
      :total="pagination.total"
      layout="total, sizes, prev, pager, next"
      style="margin-top:16px; justify-content:flex-end"
      @change="fetchList"
    />

    <!-- Create/Edit Dialog -->
    <el-dialog v-model="dialogVisible" :title="editing ? '编辑规则' : '创建规则'" width="560px" @closed="resetForm">
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px">
        <el-form-item label="规则名称" prop="name">
          <el-input v-model="form.name" maxlength="100" show-word-limit placeholder="例如：日花费超预算告警" />
        </el-form-item>
        <el-form-item label="监控指标" prop="metric">
          <el-select v-model="form.metric" style="width:100%">
            <el-option v-for="m in metricOptions" :key="m.value" :label="m.label" :value="m.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="触发条件" prop="condition">
          <el-row :gutter="12" style="width:100%">
            <el-col :span="10">
              <el-select v-model="form.condition" style="width:100%">
                <el-option v-for="c in conditionOptions" :key="c.value" :label="c.label" :value="c.value" />
              </el-select>
            </el-col>
            <el-col :span="14">
              <el-input-number v-model="form.threshold" :min="0" :precision="2" style="width:100%" placeholder="阈值" />
            </el-col>
          </el-row>
        </el-form-item>
        <el-form-item label="监控范围" prop="scope">
          <el-select v-model="form.scope" style="width:100%" @change="onScopeChange">
            <el-option v-for="s in scopeOptions" :key="s.value" :label="s.label" :value="s.value" />
          </el-select>
        </el-form-item>
        <el-form-item label="平台" prop="platform" v-if="form.scope === 'platform'">
          <el-select v-model="form.platform" style="width:100%">
            <el-option v-for="p in platforms" :key="p.code" :label="p.name" :value="p.code" />
          </el-select>
        </el-form-item>
        <el-form-item label="广告计划" prop="campaign_id" v-if="form.scope === 'campaign'">
          <el-select v-model="form.campaign_id" style="width:100%" filterable placeholder="选择广告计划">
            <el-option v-for="c in campaigns" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="检查间隔">
          <el-input-number v-model="form.check_interval" :min="1" :max="60" style="width:100%" />
          <span style="margin-left:8px;color:#909399">分钟</span>
        </el-form-item>
        <el-form-item label="通知渠道">
          <el-checkbox-group v-model="form.channels">
            <el-checkbox label="web" :disabled="true">站内通知</el-checkbox>
            <el-checkbox label="email">邮件</el-checkbox>
            <el-checkbox label="sms">短信</el-checkbox>
          </el-checkbox-group>
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="form.enabled" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { alertApi } from '@/api/alert'
import { platformApi } from '@/api/platform'
import { campaignApi } from '@/api/campaign'
import { useConfirmStore } from '@/stores/confirm'

const confirmStore = useConfirmStore()
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const list = ref<any[]>([])
const platforms = ref<any[]>([])
const campaigns = ref<any[]>([])
const editing = ref<any>(null)
const formRef = ref()

const filter = reactive({ metric: '', platform: '', enabled: null as number | null })
const pagination = reactive({ page: 1, perPage: 20, total: 0 })

const form = reactive({
  name: '',
  metric: 'cost',
  condition: 'gt',
  threshold: 0,
  scope: 'tenant',
  platform: '',
  campaign_id: null as number | null,
  check_interval: 5,
  channels: ['web'],
  enabled: true,
})

const metricOptions = [
  { value: 'cost', label: '花费' },
  { value: 'impressions', label: '展示量' },
  { value: 'clicks', label: '点击量' },
  { value: 'conversions', label: '转化数' },
  { value: 'ctr', label: '点击率(CTR)' },
  { value: 'cvr', label: '转化率(CVR)' },
  { value: 'roi', label: 'ROI' },
]

const conditionOptions = [
  { value: 'gt', label: '大于 (>)' },
  { value: 'gte', label: '大于等于 (>=)' },
  { value: 'lt', label: '小于 (<)' },
  { value: 'lte', label: '小于等于 (<=)' },
]

const scopeOptions = [
  { value: 'tenant', label: '租户整体' },
  { value: 'platform', label: '按平台' },
  { value: 'campaign', label: '按广告计划' },
]

const formRules = {
  name: [{ required: true, message: '请输入规则名称', trigger: 'blur' }],
  metric: [{ required: true, message: '请选择指标', trigger: 'change' }],
  condition: [{ required: true, message: '请选择条件', trigger: 'change' }],
  threshold: [{ required: true, message: '请输入阈值', trigger: 'blur' }],
  scope: [{ required: true, message: '请选择范围', trigger: 'change' }],
  platform: [{ required: true, message: '请选择平台', trigger: 'change' }],
  campaign_id: [{ required: true, message: '请选择广告计划', trigger: 'change' }],
}

function metricLabel(val: string) {
  return metricOptions.find(m => m.value === val)?.label ?? val
}

function conditionLabel(val: string) {
  return conditionOptions.find(c => c.value === val)?.label ?? val
}

function scopeLabel(val: string) {
  return scopeOptions.find(s => s.value === val)?.label ?? val
}

function onScopeChange() {
  form.platform = ''
  form.campaign_id = null
}

async function fetchList() {
  loading.value = true
  try {
    const params: any = { ...pagination }
    if (filter.metric) params.metric = filter.metric
    if (filter.platform) params.platform = filter.platform
    if (filter.enabled !== null && filter.enabled !== undefined) params.enabled = filter.enabled
    const data: any = await alertApi.listRules(params)
    list.value = data.list ?? []
    pagination.total = data.pagination?.total ?? 0
  } finally {
    loading.value = false
  }
}

async function handleToggle(row: any, val: boolean) {
  try {
    await alertApi.updateRule(row.id, { enabled: val ? 1 : 0 })
    row.enabled = val
    ElMessage.success(val ? '已启用' : '已禁用')
  } catch {
    // error already handled by interceptor
  }
}

function handleDeleteClick(row: any) {
  confirmStore.show({
    title: '删除告警规则',
    message: `确定要删除告警规则「${row.name}」吗？此操作不可撤销。`,
    confirmWord: row.name,
    confirmText: '确认删除',
    onConfirm: async () => {
      await alertApi.deleteRule(row.id)
      ElMessage.success('删除成功')
      fetchList()
    },
  })
}

function openCreate() {
  editing.value = null
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: any) {
  editing.value = row
  form.name = row.name
  form.metric = row.metric
  form.condition = row.condition
  form.threshold = Number(row.threshold)
  form.scope = row.scope
  form.platform = row.platform ?? ''
  form.campaign_id = row.campaign_id ?? null
  form.check_interval = row.check_interval ?? 5
  form.channels = Array.isArray(row.channels) ? [...row.channels] : ['web']
  form.enabled = row.enabled === 1 || row.enabled === true
  dialogVisible.value = true
}

function resetForm() {
  form.name = ''
  form.metric = 'cost'
  form.condition = 'gt'
  form.threshold = 0
  form.scope = 'tenant'
  form.platform = ''
  form.campaign_id = null
  form.check_interval = 5
  form.channels = ['web']
  form.enabled = true
  formRef.value?.resetFields()
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  submitting.value = true
  try {
    const payload: any = {
      name: form.name,
      metric: form.metric,
      condition: form.condition,
      threshold: form.threshold,
      scope: form.scope,
      check_interval: form.check_interval,
      channels: form.channels,
      enabled: form.enabled ? 1 : 0,
    }
    if (form.scope === 'platform') payload.platform = form.platform
    if (form.scope === 'campaign') payload.campaign_id = form.campaign_id

    if (editing.value) {
      await alertApi.updateRule(editing.value.id, payload)
      ElMessage.success('更新成功')
    } else {
      await alertApi.createRule(payload)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } finally {
    submitting.value = false
  }
}

async function loadDependencies() {
  try {
    const [pData, cData]: any[] = await Promise.all([
      platformApi.list(),
      campaignApi.list({ per_page: 200 }),
    ])
    platforms.value = pData ?? []
    campaigns.value = cData?.list ?? []
  } catch {
    // ignore load errors
  }
}

onMounted(() => {
  loadDependencies()
  fetchList()
})
</script>

<style scoped>
.alert-rule-list {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.page-header h3 {
  margin: 0;
}
.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}
</style>
