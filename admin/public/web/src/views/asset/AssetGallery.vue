<template>
  <ListPageLayout v-model:page="pagination.page" v-model:per-page="pagination.perPage" :total="pagination.total" @page-change="fetchList">
    <template #title>素材库</template>
    <template #header-actions>
      <el-upload :show-file-list="false" :http-request="handleUpload" accept="image/*,video/mp4">
        <el-button type="primary">上传素材</el-button>
      </el-upload>
    </template>
    <template #filters>
      <el-select v-model="filter.type" placeholder="类型" clearable style="width:120px" @change="fetchList">
        <el-option label="图片" value="image" /><el-option label="视频" value="video" />
      </el-select>
    </template>
    <template #table>
      <el-table :data="list" v-loading="loading" v-if="list.length > 0">
        <el-table-column label="预览" width="120">
          <template #default="{ row }">
            <el-image v-if="row.type==='image'" :src="row.url" style="width:80px;height:80px;border-radius:4px" fit="cover" :preview-src-list="[row.url]" />
            <video v-else :src="row.url" style="width:80px;height:80px;border-radius:4px" controls />
          </template>
        </el-table-column>
        <el-table-column prop="filename" label="文件名" min-width="200" show-overflow-tooltip />
        <el-table-column label="大小" width="100"><template #default="{ row }">{{ formatSize(row.size) }}</template></el-table-column>
        <el-table-column label="上传时间" width="170"><template #default="{ row }">{{ row.created_at }}</template></el-table-column>
        <el-table-column label="操作" width="140" align="center">
          <template #default="{ row }">
            <el-button size="small" @click="copyUrl(row.url)">复制URL</el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div v-else style="text-align:center;padding:60px;color:#999">暂无素材，点击"上传素材"开始</div>
    </template>
  </ListPageLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import ListPageLayout from '@/components/ListPageLayout.vue'
import { api } from '@/api/index'

const assetApi = { list(p: any) { return api.get('/assets', { params: p }) }, destroy(id: number) { return api.delete(`/assets/${id}`) } }

const loading = ref(false); const list = ref<any[]>([])
const filter = reactive({ type: '' })
const pagination = reactive({ page: 1, perPage: 20, total: 0 })

function formatSize(bytes: number): string { if (bytes < 1024) return bytes + 'B'; if (bytes < 1048576) return (bytes / 1024).toFixed(1) + 'KB'; return (bytes / 1048576).toFixed(1) + 'MB' }
function copyUrl(url: string) { navigator.clipboard.writeText(url); ElMessage.success('URL 已复制') }

async function handleUpload(req: any) {
  const form = new FormData(); form.append('file', req.file);
  try { await api.post('/assets/upload', form, { headers: { 'Content-Type': 'multipart/form-data' } }); ElMessage.success('上传成功'); fetchList() }
  catch { ElMessage.error('上传失败') }
}
async function fetchList() { loading.value = true; const data = await assetApi.list({ ...filter, ...pagination }); list.value = data.list; pagination.total = data.pagination.total; loading.value = false }
async function handleDelete(row: any) { await ElMessageBox.confirm('确定删除该素材？', '提示', { type: 'warning' }); await assetApi.destroy(row.id); ElMessage.success('已删除'); fetchList() }
onMounted(() => fetchList())
</script>
