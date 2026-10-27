<template>
  <div class="cdn-provider-list">
    <div class="page-header">
      <h3>CDN 服务商</h3>
      <el-button type="primary" @click="handleCreate">新增服务商</el-button>
    </div>

    <el-table :data="list" v-loading="loading" stripe>
      <el-table-column prop="name" label="名称" min-width="140" show-overflow-tooltip />
      <el-table-column label="驱动" width="90">
        <template #default="{ row }"><el-tag size="small">{{ row.driver }}</el-tag></template>
      </el-table-column>
      <el-table-column prop="bucket" label="Bucket" width="140" show-overflow-tooltip>
        <template #default="{ row }">{{ row.bucket || '-' }}</template>
      </el-table-column>
      <el-table-column prop="cdn_domain" label="CDN 域名" min-width="160" show-overflow-tooltip>
        <template #default="{ row }">{{ row.cdn_domain || '-' }}</template>
      </el-table-column>
      <el-table-column label="密钥" width="140">
        <template #default="{ row }">{{ row.access_key_masked || '-' }}</template>
      </el-table-column>
      <el-table-column label="默认" width="80" align="center">
        <template #default="{ row }">
          <el-tag v-if="row.is_default" size="small" type="primary">默认</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="80">
        <template #default="{ row }">
          <el-tag :type="row.enabled ? 'success' : 'info'" size="small">
            {{ row.enabled ? '启用' : '禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="340" align="center" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="handleEdit(row)">编辑</el-button>
          <el-button v-if="!row.is_default" size="small" @click="handleSetDefault(row)">设默认</el-button>
          <el-button size="small" @click="handleTest(row)">测试</el-button>
          <el-button size="small" @click="handlePurge(row)">清缓存</el-button>
          <el-button size="small" :type="row.enabled ? 'warning' : 'success'" @click="handleToggle(row)">
            {{ row.enabled ? '禁用' : '启用' }}
          </el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- Create / Edit Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="editing ? '编辑服务商' : '新增服务商'"
      width="560px"
      :close-on-click-modal="false"
    >
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="110px">
        <el-form-item label="名称" prop="name">
          <el-input v-model="form.name" maxlength="50" placeholder="如：阿里云 OSS" />
        </el-form-item>
        <el-form-item label="驱动" prop="driver">
          <el-select v-model="form.driver" style="width: 100%">
            <el-option v-for="d in drivers" :key="d.value" :label="d.label" :value="d.value" />
          </el-select>
        </el-form-item>
        <template v-if="form.driver !== 'local'">
          <el-form-item label="Bucket" prop="bucket">
            <el-input v-model="form.bucket" maxlength="100" />
          </el-form-item>
          <el-form-item label="Region" prop="region">
            <el-input v-model="form.region" maxlength="50" placeholder="如：oss-cn-hangzhou" />
          </el-form-item>
          <el-form-item v-if="form.driver === 's3'" label="Endpoint" prop="endpoint">
            <el-input v-model="form.endpoint" maxlength="150" placeholder="如：https://xxx.r2.cloudflarestorage.com" />
          </el-form-item>
          <el-form-item label="Access Key" prop="access_key">
            <el-input v-model="form.access_key" maxlength="100" />
          </el-form-item>
          <el-form-item label="Secret Key" :prop="editing ? '' : 'secret_key'">
            <el-input
              v-model="form.secret_key"
              type="password"
              show-password
              maxlength="200"
              :placeholder="editing ? '留空则不修改' : '请输入 Secret Key'"
            />
          </el-form-item>
        </template>
        <el-form-item label="CDN 域名" prop="cdn_domain">
          <el-input v-model="form.cdn_domain" maxlength="100" placeholder="可选，如：cdn.example.com" />
        </el-form-item>
        <el-form-item label="CDN 驱动" prop="cdn_driver">
          <el-input v-model="form.cdn_driver" maxlength="30" placeholder="可选，如：aliyun/tencent" />
        </el-form-item>
        <el-form-item label="CDN Token" prop="cdn_token">
          <el-input v-model="form.cdn_token" type="password" show-password maxlength="200" />
        </el-form-item>
        <el-form-item label="启用">
          <el-switch v-model="form.enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>

    <!-- Purge Dialog -->
    <el-dialog v-model="purgeVisible" title="清缓存" width="480px" :close-on-click-modal="false">
      <el-input
        v-model="purgeUrls"
        type="textarea"
        :rows="6"
        placeholder="每行一个 URL，支持空格/逗号分隔"
      />
      <template #footer>
        <el-button @click="purgeVisible = false">取消</el-button>
        <el-button type="primary" :loading="purging" @click="submitPurge">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { cdnApi } from '@/api/cdn'
import { useConfirmStore } from '@/stores/confirm'

const confirmStore = useConfirmStore()
const list = ref<any[]>([])
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editing = ref(false)
const formRef = ref<FormInstance>()

const drivers = [
  { label: '阿里云 OSS', value: 'oss' },
  { label: '腾讯云 COS', value: 'cos' },
  { label: 'AWS S3', value: 's3' },
  { label: '本地存储', value: 'local' },
]

const form = reactive({
  id: null as number | null,
  name: '',
  driver: 'oss',
  bucket: '',
  region: '',
  endpoint: '',
  access_key: '',
  secret_key: '',
  cdn_domain: '',
  cdn_driver: '',
  cdn_token: '',
  enabled: 1,
})

const formRules: FormRules = {
  name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
  driver: [{ required: true, message: '请选择驱动', trigger: 'change' }],
  secret_key: [{ required: true, message: '请输入 Secret Key', trigger: 'blur' }],
}

const purgeVisible = ref(false)
const purgeProvider = ref<any>(null)
const purgeUrls = ref('')
const purging = ref(false)

async function fetchList() {
  loading.value = true
  try {
    const data: any = await cdnApi.list()
    list.value = Array.isArray(data) ? data : (data?.list || [])
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.id = null
  form.name = ''
  form.driver = 'oss'
  form.bucket = ''
  form.region = ''
  form.endpoint = ''
  form.access_key = ''
  form.secret_key = ''
  form.cdn_domain = ''
  form.cdn_driver = ''
  form.cdn_token = ''
  form.enabled = 1
}

function handleCreate() {
  editing.value = false
  resetForm()
  dialogVisible.value = true
}

function handleEdit(row: any) {
  editing.value = true
  form.id = row.id
  form.name = row.name
  form.driver = row.driver
  form.bucket = row.bucket || ''
  form.region = row.region || ''
  form.endpoint = row.endpoint || ''
  form.access_key = row.access_key || ''
  form.secret_key = ''
  form.cdn_domain = row.cdn_domain || ''
  form.cdn_driver = row.cdn_driver || ''
  form.cdn_token = ''
  form.enabled = row.enabled ? 1 : 0
  dialogVisible.value = true
}

function handleToggle(row: any) {
  const action = row.enabled ? '禁用' : '启用'
  confirmStore.show({
    title: `${action}服务商`,
    message: `确定要${action}服务商「${row.name}」吗？`,
    confirmWord: row.name,
    confirmText: `确认${action}`,
    onConfirm: async () => {
      await cdnApi.toggle(row.id)
      ElMessage.success(`${action}成功`)
      fetchList()
    },
  })
}

function handleDelete(row: any) {
  confirmStore.show({
    title: '删除服务商',
    message: `确定要删除服务商「${row.name}」吗？${row.is_default ? '它是默认服务商，删除后默认将自动转移。' : ''}`,
    confirmWord: row.name,
    confirmText: '确认删除',
    onConfirm: async () => {
      await cdnApi.destroy(row.id)
      ElMessage.success('已删除')
      fetchList()
    },
  })
}

function handleSetDefault(row: any) {
  confirmStore.show({
    title: '设为默认',
    message: `确定将「${row.name}」设为默认服务商吗？`,
    confirmText: '确认',
    requireTyping: false,
    onConfirm: async () => {
      await cdnApi.setDefault(row.id)
      ElMessage.success('已设为默认')
      fetchList()
    },
  })
}

async function handleTest(row: any) {
  await cdnApi.test(row.id)
  ElMessage.success('连通测试通过')
}

function handlePurge(row: any) {
  purgeProvider.value = row
  purgeUrls.value = ''
  purgeVisible.value = true
}

async function submitPurge() {
  const paths = purgeUrls.value
    .split(/[\n,，\s]+/)
    .map((u: string) => u.trim())
    .filter(Boolean)
  if (paths.length === 0) {
    ElMessage.warning('请至少输入一个 URL')
    return
  }
  purging.value = true
  try {
    await cdnApi.purge(purgeProvider.value.id, paths)
    ElMessage.success(`已提交 ${paths.length} 个 URL 的清缓存请求`)
    purgeVisible.value = false
  } finally {
    purging.value = false
  }
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  submitting.value = true
  try {
    const payload: any = {
      name: form.name,
      driver: form.driver,
      enabled: form.enabled,
      cdn_domain: form.cdn_domain,
      cdn_driver: form.cdn_driver,
      cdn_token: form.cdn_token,
    }
    if (form.driver !== 'local') {
      payload.bucket = form.bucket
      payload.region = form.region
      payload.endpoint = form.endpoint
      payload.access_key = form.access_key
    }
    if (form.secret_key) payload.secret_key = form.secret_key

    if (editing.value && form.id) {
      await cdnApi.update(form.id, payload)
      ElMessage.success('更新成功')
    } else {
      await cdnApi.create(payload)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } finally {
    submitting.value = false
  }
}

onMounted(fetchList)
</script>

<style scoped>
.cdn-provider-list { background: #fff; border-radius: 8px; padding: 16px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h3 { margin: 0; }
</style>
