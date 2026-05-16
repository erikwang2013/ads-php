# Admin Page Generator

Generate Vue3 admin pages following the project's established patterns.

## Tech Stack
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## File Structure
```
admin/public/web/src/
├── api/{module}.ts          # Axios API module
├── views/{module}/          # Page components
├── components/              # Shared components
├── stores/                  # Pinia stores
└── router/index.ts          # Route definitions
```

## API Module Template
```typescript
// admin/public/web/src/api/example.ts
import { api } from './index'

export const exampleApi = {
  list(params?: any) { return api.get('/example', { params }) },
  create(data: any) { return api.post('/example', data) },
  update(id: number, data: any) { return api.put(`/example/${id}`, data) },
  destroy(id: number) { return api.delete(`/example/${id}`) },
}
```

## List Page Template
```vue
<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { exampleApi } from '@/api/example'

const loading = ref(false)
const list = ref<any[]>([])
const pagination = reactive({ page: 1, perPage: 20, total: 0 })

async function fetchList() {
  loading.value = true
  const data = await exampleApi.list({ ...pagination })
  list.value = data.list; pagination.total = data.pagination.total
  loading.value = false
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除？', '提示', { type: 'warning' })
  await exampleApi.destroy(row.id); ElMessage.success('已删除'); fetchList()
}

onMounted(fetchList)
</script>
```

## Rules

1. **Copyright**: Every `.ts`/`.vue` file starts with `Copyright (c) 2026 erik...`
2. **API client**: Use the pre-configured `api` instance from `@/api/index` — it auto-unwraps `ApiResponse<T>` envelope
3. **Money display**: Use `formatFen()` from `@/utils/format` — all backend values are in fen (分)
4. **Platform badges**: Use `<PlatformBadge :platform="row.platform" />` component
5. **Metric cards**: Use `<MetricCard>` with format='money'|'number'|'percent'
6. **Routes**: Add to `router/index.ts` inside the `children` array
7. **SideNav**: Add menu item in `components/layout/SideNav.vue`

## Adding a New Page — Full Checklist
1. Create `api/{module}.ts`
2. Create `views/{module}/{Page}.vue`
3. Add route in `router/index.ts`
4. Add menu item in `components/layout/SideNav.vue`
5. Run `npx vue-tsc --noEmit` to verify TypeScript
