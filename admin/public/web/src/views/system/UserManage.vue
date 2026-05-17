<template>
  <div class="user-manage">
    <div class="page-header">
      <h3>用户管理</h3>
      <el-button type="primary" @click="handleCreate">新增用户</el-button>
    </div>

    <div class="filters">
      <el-input
        v-model="filters.keyword"
        placeholder="搜索用户名/姓名/邮箱"
        clearable
        style="width: 240px"
        @change="fetchList"
      />
      <el-select
        v-model="filters.role_id"
        placeholder="角色筛选"
        clearable
        style="width: 160px"
        @change="fetchList"
      >
        <el-option
          v-for="r in roleList"
          :key="r.id"
          :label="r.name"
          :value="r.id"
        />
      </el-select>
    </div>

    <el-table :data="list" v-loading="loading" stripe>
      <el-table-column prop="id" label="ID" width="180" show-overflow-tooltip />
      <el-table-column prop="username" label="用户名" width="140" />
      <el-table-column prop="name" label="姓名" width="120" />
      <el-table-column prop="email" label="邮箱" min-width="180" show-overflow-tooltip />
      <el-table-column label="角色" width="120">
        <template #default="{ row }">
          <el-tag size="small" type="info">{{ row.role_name || '-' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="80">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
            {{ row.status === 1 ? '正常' : '已禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="last_login_at" label="最后登录" width="170" />
      <el-table-column label="操作" width="180" align="center" fixed="right">
        <template #default="{ row }">
          <el-button size="small" type="primary" @click="handleEdit(row)">编辑</el-button>
          <el-button
            size="small"
            :type="row.status === 1 ? 'warning' : 'success'"
            @click="handleToggle(row)"
          >
            {{ row.status === 1 ? '禁用' : '启用' }}
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="pagination.page"
      v-model:page-size="pagination.perPage"
      :total="pagination.total"
      layout="total, sizes, prev, pager, next"
      style="margin-top: 16px; justify-content: flex-end"
      @change="fetchList"
    />

    <!-- Create / Edit Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="editing ? '编辑用户' : '新增用户'"
      width="520px"
      :close-on-click-modal="false"
    >
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="80px">
        <el-form-item label="用户名" prop="username">
          <el-input v-model="form.username" :disabled="editing" maxlength="50" />
        </el-form-item>
        <el-form-item label="密码" :prop="editing ? '' : 'password'">
          <el-input
            v-model="form.password"
            type="password"
            show-password
            :placeholder="editing ? '留空则不修改密码' : '请输入密码'"
            maxlength="50"
          />
        </el-form-item>
        <el-form-item label="姓名" prop="name">
          <el-input v-model="form.name" maxlength="50" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="form.email" maxlength="100" />
        </el-form-item>
        <el-form-item label="角色" prop="role_id">
          <el-select v-model="form.role_id" style="width: 100%">
            <el-option
              v-for="r in roleList"
              :key="r.id"
              :label="r.name"
              :value="r.id"
            />
          </el-select>
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
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { adminApi } from '@/api/admin'
import { useConfirmStore } from '@/stores/confirm'

const confirmStore = useConfirmStore()
const list = ref<any[]>([])
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const editing = ref(false)
const formRef = ref<FormInstance>()
const roleList = ref<any[]>([])

const filters = reactive({
  keyword: '',
  role_id: '' as string | number,
})

const pagination = reactive({
  page: 1,
  perPage: 15,
  total: 0,
})

const form = reactive({
  id: null as number | null,
  username: '',
  password: '',
  name: '',
  email: '',
  role_id: 0,
})

const formRules: FormRules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
}

async function fetchList() {
  loading.value = true
  try {
    const data: any = await adminApi.listUsers({
      page: pagination.page,
      per_page: pagination.perPage,
      keyword: filters.keyword,
      role_id: filters.role_id,
    })
    list.value = data.list
    pagination.total = data.total
  } finally {
    loading.value = false
  }
}

async function fetchRoles() {
  try {
    roleList.value = await adminApi.listRoles() as any
  } catch {
    // ignore
  }
}

function handleCreate() {
  editing.value = false
  form.id = null
  form.username = ''
  form.password = ''
  form.name = ''
  form.email = ''
  form.role_id = roleList.value.length > 0 ? roleList.value[0].id : 0
  dialogVisible.value = true
}

function handleEdit(row: any) {
  editing.value = true
  form.id = row.id
  form.username = row.username
  form.password = ''
  form.name = row.name || ''
  form.email = row.email || ''
  form.role_id = row.role_id
  dialogVisible.value = true
}

function handleToggle(row: any) {
  const action = row.status === 1 ? '禁用' : '启用'
  confirmStore.show({
    title: `${action}用户`,
    message: `确定要${action}用户「${row.username}」吗？${action === '禁用' ? '禁用后该用户将无法登录系统。' : ''}`,
    confirmWord: row.username,
    confirmText: `确认${action}`,
    onConfirm: async () => {
      await adminApi.updateUser(row.id, { status: row.status === 1 ? 0 : 1 })
      ElMessage.success(`${action}成功`)
      fetchList()
    },
  })
}

async function submitForm() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  submitting.value = true
  try {
    const payload: any = {
      name: form.name,
      email: form.email,
      role_id: form.role_id,
    }
    if (!editing.value) {
      payload.username = form.username
      payload.password = form.password
    } else if (form.password) {
      payload.password = form.password
    }

    if (editing.value && form.id) {
      await adminApi.updateUser(form.id, payload)
      ElMessage.success('更新成功')
    } else {
      await adminApi.createUser(payload)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchList()
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchRoles()
  fetchList()
})
</script>

<style scoped>
.user-manage .page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.user-manage .page-header h3 {
  margin: 0;
}
.user-manage .filters {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}
</style>
