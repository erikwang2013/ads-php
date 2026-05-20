<template>
  <ListPageLayout v-model:page="pagination.page" v-model:per-page="pagination.perPage" :total="pagination.total" @page-change="fetchList">
    <template #title>广告创意</template>
    <template #filters>
      <el-select v-model="filter.platform" placeholder="平台" clearable style="width:140px" @change="fetchList">
        <el-option v-for="p in platforms" :key="p.code" :label="p.name" :value="p.code" />
      </el-select>
      <el-select v-model="filter.campaign_id" placeholder="所属计划" clearable style="width:200px" @change="fetchList">
        <el-option v-for="c in campaigns" :key="c.id" :label="c.name" :value="c.id" />
      </el-select>
      <el-select v-model="filter.media_type" placeholder="素材类型" clearable style="width:120px" @change="fetchList">
        <el-option label="图片" value="image" /><el-option label="视频" value="video" /><el-option label="文字" value="text" />
      </el-select>
    </template>
    <template #table>
      <el-table :data="list" v-loading="loading">
        <el-table-column label="预览" width="80">
          <template #default="{ row }"><el-image v-if="row.media_urls" :src="getFirstMediaUrl(row.media_urls)" style="width:48px;height:48px;border-radius:4px" fit="cover" /></template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
        <el-table-column label="描述" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">{{ truncate(row.description, 50) }}</template>
        </el-table-column>
        <el-table-column label="类型" width="80"><template #default="{ row }"><el-tag size="small">{{ row.media_type }}</el-tag></template></el-table-column>
        <el-table-column label="所属计划" min-width="140"><template #default="{ row }">{{ row.campaign_name }}</template></el-table-column>
        <el-table-column label="广告组" min-width="140"><template #default="{ row }">{{ row.ad_group_name }}</template></el-table-column>
      </el-table>
    </template>
  </ListPageLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import ListPageLayout from '@/components/ListPageLayout.vue'
import { creativeApi } from '@/api/creative'
import { campaignApi } from '@/api/campaign'
import { platformApi } from '@/api/platform'

const loading = ref(false)
const list = ref<any[]>([]); const platforms = ref<any[]>([]); const campaigns = ref<any[]>([])
const filter = reactive({ platform: '', campaign_id: '', media_type: '' })
const pagination = reactive({ page: 1, perPage: 20, total: 0 })

function truncate(text: string | null, max: number): string { if (!text) return ''; return text.length > max ? text.slice(0, max) + '...' : text }
function getFirstMediaUrl(urls: string): string { try { const arr = JSON.parse(urls); return arr[0] || '' } catch { return '' } }
async function fetchList() {
  loading.value = true
  const data = await creativeApi.list({ ...filter, ...pagination })
  list.value = data.list; pagination.total = data.pagination.total; loading.value = false
}
onMounted(async () => {
  const [p, c] = await Promise.all([platformApi.list(), campaignApi.list({ per_page: 100 })])
  platforms.value = p; campaigns.value = c.list ?? []; fetchList()
})
</script>
